<?php

namespace App\Support\GoogleMerchant;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Punto único de sincronización con Google Merchant Center. Coordina
 * validator + mapper + client, pero no duplica la lógica de ninguno:
 * decide SI y CUÁNDO llamar a Google, ellos deciden CÓMO. Pensado para
 * ejecutarse dentro de un Job en cola (Fase 6) — nunca se llama
 * directamente durante una petición HTTP del usuario.
 */
class GoogleMerchantService
{
    /**
     * Mensajes del validador que dependen únicamente del negocio, no del
     * producto en sí — un producto bloqueado solo por estos motivos no
     * "requiere ajustes" del emprendedor, simplemente no puede publicarse
     * todavía (0.9 del TODO de esta integración: no mostrar como error lo
     * que el emprendedor no puede arreglar desde el producto).
     */
    public function __construct(
        private readonly GoogleMerchantClient $client,
        private readonly GoogleMerchantProductMapper $mapper,
        private readonly GoogleMerchantProductValidator $validator,
    ) {}

    public function syncProduct(Product $product): void
    {
        if (! config('services.google_merchant.enabled')) {
            return;
        }

        $product->loadMissing(['business.storefront', 'business.category', 'media']);

        $errors = $this->validator->validate($product);

        if ($errors !== []) {
            // El producto YA estaba visible en Google y dejó de ser
            // elegible (se archivó, se agotó el negocio, etc.): hay que
            // retirarlo de verdad, no solo cambiar el estado local
            // (criterio de aceptación: "elimino/despublico el producto,
            // se retira de Google"). Si `deleteProduct` lanza (error
            // temporal), la excepción sube y el Job reintenta más tarde
            // sin marcar `requiere_ajustes` todavía.
            if ($product->google_merchant_status === 'publicado') {
                $this->deleteProduct($product);
            }

            $this->markRequiresAttention($product, $errors);

            return;
        }

        $hash = $this->hash($product);

        // Nada relevante cambió desde el último envío exitoso: no llamar
        // a Google (0.7 del TODO de esta integración).
        if ($product->google_merchant_status === 'publicado' && $product->google_merchant_synced_hash === $hash) {
            return;
        }

        $this->push($product, $hash);
    }

    public function createProduct(Product $product): void
    {
        $this->syncProduct($product);
    }

    public function updateProduct(Product $product): void
    {
        $this->syncProduct($product);
    }

    public function deleteProduct(Product $product): void
    {
        if (! config('services.google_merchant.enabled')) {
            return;
        }

        if (blank($product->google_merchant_product_id) && $product->google_merchant_status === 'no_publicado') {
            return;
        }

        try {
            $this->client->delete($product);
        } catch (ConnectionException $exception) {
            Log::warning("[GoogleMerchant] Product {$product->id} Action DELETE FAILED (temporal) {$exception->getMessage()}");

            throw $exception;
        } catch (RequestException $exception) {
            if ($this->isTemporary($exception)) {
                Log::warning("[GoogleMerchant] Product {$product->id} Action DELETE FAILED (temporal) HTTP {$exception->response->status()}");

                throw $exception;
            }

            $this->recordError($product, 'DELETE', $this->friendlyError($exception));

            return;
        }

        $product->forceFill([
            'google_merchant_status' => 'no_publicado',
            'google_merchant_product_id' => null,
            'google_merchant_last_sync_at' => now(),
            'google_merchant_last_error' => null,
            'google_merchant_synced_hash' => null,
        ])->save();

        app(RecordAuditLog::class)->handle(null, 'google_merchant.deleted', $product, [
            'business_id' => $product->business_id,
        ]);

        Log::info("[GoogleMerchant] Product {$product->id} Business {$product->business_id} Action DELETE SUCCESS");
    }

    public function syncBusinessProducts(Business $business): void
    {
        $business->products()
            ->where('type', 'producto')
            ->with(['business.storefront', 'business.category', 'media'])
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    $this->syncProduct($product);
                }
            });
    }

    public function syncAllProducts(): void
    {
        Product::query()
            ->where('type', 'producto')
            ->whereHas('business', fn ($query) => $query->where('google_merchant_enabled', true))
            ->with(['business.storefront', 'business.category', 'media'])
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    $this->syncProduct($product);
                }
            });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProductStatus(Product $product): ?array
    {
        if (! config('services.google_merchant.enabled')) {
            return null;
        }

        return $this->client->get($product);
    }

    /**
     * Hash de los campos que le importan a Google — si no cambia entre
     * dos llamadas, no hay nada nuevo que enviar.
     */
    public function hash(Product $product): string
    {
        return hash('sha256', (string) json_encode($this->mapper->apiPayload($product)));
    }

    private function push(Product $product, string $hash): void
    {
        try {
            $response = $this->client->insert($product);
        } catch (ConnectionException $exception) {
            Log::warning("[GoogleMerchant] Product {$product->id} Action SYNC FAILED (temporal) {$exception->getMessage()}");

            throw $exception;
        } catch (RequestException $exception) {
            if ($this->isTemporary($exception)) {
                Log::warning("[GoogleMerchant] Product {$product->id} Action SYNC FAILED (temporal) HTTP {$exception->response->status()}");

                throw $exception;
            }

            $this->recordError($product, 'SYNC', $this->friendlyError($exception));

            return;
        }

        if ($response === null) {
            $this->recordError($product, 'SYNC', 'El producto no cumple los requisitos mínimos de Google.');

            return;
        }

        $product->forceFill([
            'google_merchant_status' => 'publicado',
            'google_merchant_product_id' => $response['name'] ?? $product->google_merchant_product_id,
            'google_merchant_last_sync_at' => now(),
            'google_merchant_last_error' => null,
            'google_merchant_synced_hash' => $hash,
        ])->save();

        app(RecordAuditLog::class)->handle(null, 'google_merchant.synced', $product, [
            'business_id' => $product->business_id,
        ]);

        Log::info("[GoogleMerchant] Product {$product->id} Business {$product->business_id} Action SYNC SUCCESS");
    }

    /**
     * @param  list<string>  $errors
     */
    private function markRequiresAttention(Product $product, array $errors): void
    {
        $business = $product->business;

        $blockedOnlyByBusiness = ! $business
            || ! $business->google_merchant_enabled
            || ! $business->isPublished()
            || blank($business->external_seller_id);

        $status = match (true) {
            ! $product->isPublished() => 'no_publicado',
            $blockedOnlyByBusiness => 'no_publicado',
            default => 'requiere_ajustes',
        };

        $product->forceFill([
            'google_merchant_status' => $status,
            'google_merchant_last_error' => implode(' ', $errors),
        ])->save();
    }

    private function recordError(Product $product, string $action, string $message): void
    {
        $product->forceFill([
            'google_merchant_status' => 'error',
            'google_merchant_last_error' => $message,
        ])->save();

        app(RecordAuditLog::class)->handle(null, 'google_merchant.failed', $product, [
            'business_id' => $product->business_id,
            'action' => $action,
            'message' => $message,
        ]);

        Log::error("[GoogleMerchant] Product {$product->id} Action {$action} FAILED Reason: {$message}");
    }

    /**
     * Distingue errores temporales (reintentables: rate limit, 5xx) de
     * permanentes (producto/vendedor inválido según Google) — los
     * primeros se relanzan para que el Job en cola reintente con backoff;
     * los segundos se guardan y no se reintentan solos.
     */
    private function isTemporary(RequestException $exception): bool
    {
        $status = $exception->response->status();

        return $status === 429 || $status >= 500;
    }

    /**
     * Motivo crudo de Google, acotado y sin datos sensibles — la
     * traducción a un mensaje amigable para el emprendedor vive en la
     * Fase 9 (panel del vendedor), no aquí.
     */
    private function friendlyError(RequestException $exception): string
    {
        $reason = $exception->response->json('error.message') ?? $exception->getMessage();

        return Str::limit((string) $reason, 500);
    }
}
