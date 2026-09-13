<?php

namespace App\Console\Commands\GoogleMerchant;

use App\Domain\Storefronts\Jobs\SyncProductToGoogleMerchant;
use App\Domain\Storefronts\Models\Product;
use App\Support\GoogleMerchant\GoogleMerchantClient;
use App\Support\GoogleMerchant\GoogleMerchantProductMapper;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

#[Signature('google-merchant:sync
    {--product= : Sincroniza únicamente este ID de producto}
    {--business= : Sincroniza todos los productos elegibles de este negocio}
    {--all : Sincroniza todos los negocios habilitados, ignorando la conciliación por fecha}
    {--failed : Reintenta únicamente los productos que quedaron en estado de error}
    {--dry-run : Valida el catálogo sin enviarlo a Google}
    {--allow-non-production : Autoriza explícitamente un envío fuera de producción}')]
#[Description('Sincroniza (o valida) con Google Merchant Center los productos elegibles de los negocios habilitados.')]
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

        return $this->option('dry-run')
            ? $this->runDryRun($mapper)
            : $this->dispatchJobs();
    }

    /**
     * Alcance según los flags. Sin `--product`/`--business`/`--failed`
     * (o con `--all`), aplica la conciliación: no toca lo que ya está
     * `publicado` en Google y no cambió desde el último envío — comparar
     * `updated_at` contra `google_merchant_last_sync_at` evita incluso
     * encolar un Job que el hash de `GoogleMerchantService` terminaría
     * descartando de todos modos.
     *
     * @return Builder<Product>
     */
    private function scopedQuery(): Builder
    {
        $query = Product::query()
            ->where('type', 'producto')
            ->where('status', 'publicado')
            ->whereHas('business', fn (Builder $query) => $query
                ->where('status', 'publicado')
                ->where('google_merchant_enabled', true));

        if ($productId = $this->option('product')) {
            return $query->whereKey($productId);
        }

        if ($businessId = $this->option('business')) {
            return $query->where('business_id', $businessId);
        }

        if ($this->option('failed')) {
            return $query->where('google_merchant_status', 'error');
        }

        if ($this->option('all')) {
            return $query;
        }

        return $query->where(fn (Builder $query) => $query
            ->whereNull('google_merchant_last_sync_at')
            ->orWhere('google_merchant_status', '!=', 'publicado')
            ->orWhereColumn('updated_at', '>', 'google_merchant_last_sync_at'));
    }

    private function runDryRun(GoogleMerchantProductMapper $mapper): int
    {
        $valid = 0;
        $skipped = 0;
        $failed = 0;

        $this->scopedQuery()
            ->with(['business.category', 'business.storefront', 'media'])
            ->chunkById(100, function ($products) use ($mapper, &$valid, &$skipped, &$failed): void {
                foreach ($products as $product) {
                    try {
                        $mapper->apiPayload($product) === null ? $skipped++ : $valid++;
                    } catch (Throwable $exception) {
                        $failed++;
                        $this->error("Producto {$product->id}: {$exception->getMessage()}");
                    }
                }
            });

        $this->info("Productos válidos: {$valid}; omitidos: {$skipped}; fallidos: {$failed}.");

        return self::SUCCESS;
    }

    /**
     * A diferencia del modo anterior (llamaba a Google síncronamente
     * dentro del propio comando), esto solo encola — la app debe seguir
     * funcionando con normalidad y con reintentos propios aunque Google
     * esté caído o haya miles de productos (0.10/0.11 del TODO de esta
     * integración).
     */
    private function dispatchJobs(): int
    {
        $count = 0;

        $this->scopedQuery()->chunkById(100, function ($products) use (&$count): void {
            foreach ($products as $product) {
                SyncProductToGoogleMerchant::dispatch($product->id);
                $count++;
            }
        });

        $this->info("Productos encolados para sincronizar: {$count}.");

        return self::SUCCESS;
    }
}
