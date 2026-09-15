<?php

namespace App\Support\GoogleMerchant;

use App\Domain\Storefronts\Models\Product;
use Closure;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleMerchantClient
{
    private const SCOPE = 'https://www.googleapis.com/auth/content';

    private ?string $accessToken = null;

    public function __construct(
        private readonly GoogleMerchantProductMapper $mapper,
        private readonly ?Closure $accessTokenResolver = null,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function insert(Product $product): ?array
    {
        $payload = $this->mapper->apiPayload($product);

        if ($payload === null) {
            return null;
        }

        return $this->request()
            ->withQueryParameters(['dataSource' => $this->dataSourceName()])
            ->post($this->url("products/v1/accounts/{$this->accountId()}/productInputs:insert"), $payload)
            ->throw()
            ->json();
    }

    /**
     * Retira un producto de Google Merchant Center (cuando se archiva,
     * suspende o el negocio se desactiva). Un 404 significa que ya no
     * existe del lado de Google —o nunca se sincronizó— y se trata como
     * éxito, no como error.
     */
    public function delete(Product $product): void
    {
        $response = $this->request()
            ->withQueryParameters(['dataSource' => $this->dataSourceName()])
            ->delete($this->url("products/v1/accounts/{$this->accountId()}/productInputs/{$this->mapper->productResourceId($product)}"));

        if ($response->status() === 404) {
            return;
        }

        $response->throw();
    }

    /**
     * Estado procesado del producto en Merchant Center (incluye
     * `productStatus` con destinos e issues). El servicio `productstatuses`
     * de Content API fue eliminado en Merchant API: este es el reemplazo
     * vigente. `null` si Google todavía no tiene ningún producto con este
     * identificador (nunca sincronizado, o eliminado).
     *
     * @return array<string, mixed>|null
     */
    public function get(Product $product): ?array
    {
        $response = $this->request()
            ->get($this->url("products/v1/accounts/{$this->accountId()}/products/{$this->mapper->productResourceId($product)}"));

        if ($response->status() === 404) {
            return null;
        }

        return $response->throw()->json();
    }

    /**
     * Inventario local (fichas locales sin costo / anuncios de inventario
     * local) — recurso `LocalInventory` aparte de `productInputs`, sin
     * `dataSource` (confirmado contra la documentación vigente: esta
     * familia de la Merchant API no lo requiere). `null` si el producto no
     * tiene `google_business_store_code` configurado.
     *
     * @return array<string, mixed>|null
     */
    public function insertLocalInventory(Product $product): ?array
    {
        $payload = $this->mapper->localInventoryPayload($product);

        if ($payload === null) {
            return null;
        }

        return $this->request()
            ->post($this->url("inventories/v1/accounts/{$this->accountId()}/products/{$this->mapper->productResourceId($product)}/localInventories:insert"), $payload)
            ->throw()
            ->json();
    }

    /**
     * Retira el inventario local de un `storeCode` específico. Un 404 se
     * trata como éxito, igual que `delete()`.
     */
    public function deleteLocalInventory(Product $product, string $storeCode): void
    {
        $response = $this->request()
            ->delete($this->url("inventories/v1/accounts/{$this->accountId()}/products/{$this->mapper->productResourceId($product)}/localInventories/{$storeCode}"));

        if ($response->status() === 404) {
            return;
        }

        $response->throw();
    }

    /**
     * Registra una sola vez el proyecto de Google Cloud ante Merchant Center.
     *
     * @return array<string, mixed>
     */
    public function registerDeveloper(string $email): array
    {
        return $this->request()
            ->post($this->url("accounts/v1/accounts/{$this->accountId()}/developerRegistration:registerGcp"), [
                'developerEmail' => $email,
            ])
            ->throw()
            ->json();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function dataSources(): array
    {
        return $this->request()
            ->get($this->url("datasources/v1/accounts/{$this->accountId()}/dataSources"))
            ->throw()
            ->json('dataSources', []);
    }

    public function assertConfigured(bool $requireDataSource = true): void
    {
        $required = [
            'GOOGLE_MERCHANT_ACCOUNT_ID' => config('services.google_merchant.account_id'),
            'GOOGLE_MERCHANT_CREDENTIALS' => config('services.google_merchant.credentials'),
        ];

        if ($requireDataSource) {
            $required['GOOGLE_MERCHANT_DATA_SOURCE_ID'] = config('services.google_merchant.data_source_id');
        }

        $missing = array_keys(array_filter($required, fn ($value) => blank($value)));

        if ($missing !== []) {
            throw new RuntimeException('Falta configurar: '.implode(', ', $missing).'.');
        }

        $this->credentialsPath();
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withToken($this->token())
            ->timeout((int) config('services.google_merchant.timeout'))
            ->retry(2, 500, throw: false);
    }

    private function token(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        if ($this->accessTokenResolver !== null) {
            return $this->accessToken = ($this->accessTokenResolver)();
        }

        $contents = file_get_contents($this->credentialsPath());

        if ($contents === false) {
            throw new RuntimeException('No fue posible cargar las credenciales de Google.');
        }

        $credentials = json_decode(
            $contents,
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (($credentials['type'] ?? null) !== 'service_account') {
            throw new RuntimeException('Las credenciales de Google deben ser de tipo service_account.');
        }

        $token = (new ServiceAccountCredentials(self::SCOPE, $credentials))->fetchAuthToken();
        $accessToken = $token['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Google no devolvió un token de acceso válido.');
        }

        return $this->accessToken = $accessToken;
    }

    private function credentialsPath(): string
    {
        $configuredPath = (string) config('services.google_merchant.credentials');
        $path = str_starts_with($configuredPath, DIRECTORY_SEPARATOR)
            ? $configuredPath
            : base_path($configuredPath);

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("No se puede leer el archivo de credenciales de Google: {$path}");
        }

        return $path;
    }

    private function accountId(): string
    {
        return (string) config('services.google_merchant.account_id');
    }

    private function dataSourceName(): string
    {
        $dataSource = (string) config('services.google_merchant.data_source_id');

        return str_starts_with($dataSource, 'accounts/')
            ? $dataSource
            : "accounts/{$this->accountId()}/dataSources/{$dataSource}";
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.google_merchant.endpoint'), '/').'/'.$path;
    }
}
