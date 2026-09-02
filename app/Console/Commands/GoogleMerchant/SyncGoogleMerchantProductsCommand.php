<?php

namespace App\Console\Commands\GoogleMerchant;

use App\Domain\Storefronts\Models\Product;
use App\Support\GoogleMerchant\GoogleMerchantClient;
use App\Support\GoogleMerchant\GoogleMerchantProductMapper;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('google-merchant:sync {--product= : Sincroniza únicamente este ID de producto} {--dry-run : Valida el catálogo sin enviarlo a Google} {--allow-non-production : Autoriza explícitamente un envío fuera de producción}')]
#[Description('Agrega o actualiza en Google Merchant Center los productos públicos elegibles.')]
class SyncGoogleMerchantProductsCommand extends Command
{
    public function handle(GoogleMerchantClient $client, GoogleMerchantProductMapper $mapper): int
    {
        if (! $this->option('dry-run') && ! app()->environment('production') && ! $this->option('allow-non-production')) {
            $this->error('Por seguridad, los envíos reales solo se permiten en producción. Usa --dry-run para validar.');

            return self::FAILURE;
        }

        if (! $this->option('dry-run')) {
            try {
                $client->assertConfigured();
            } catch (Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }
        }

        $query = Product::query()
            ->where('type', 'producto')
            ->where('status', 'publicado')
            ->whereHas('business', fn ($query) => $query->where('status', 'publicado'))
            ->with(['business.category', 'business.storefront', 'media']);

        if ($productId = $this->option('product')) {
            $query->whereKey($productId);
        }

        $synced = 0;
        $skipped = 0;
        $failed = 0;

        $query->chunkById(100, function ($products) use ($client, $mapper, &$synced, &$skipped, &$failed): void {
            foreach ($products as $product) {
                try {
                    $result = $this->option('dry-run') ? $mapper->apiPayload($product) : $client->insert($product);

                    $result === null ? $skipped++ : $synced++;
                } catch (Throwable $exception) {
                    $failed++;
                    $this->error("Producto {$product->id}: {$exception->getMessage()}");
                }
            }
        });

        $verb = $this->option('dry-run') ? 'válidos' : 'sincronizados';
        $this->info("Productos {$verb}: {$synced}; omitidos: {$skipped}; fallidos: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
