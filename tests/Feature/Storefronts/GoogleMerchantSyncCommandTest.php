<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Jobs\SyncProductToGoogleMerchant;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Fase 10 de la integración con Google Merchant: `google-merchant:sync`
 * encola trabajo (nunca llama a Google síncronamente desde el comando) y
 * respeta el alcance de cada flag (`--business`, `--failed`, `--all`,
 * conciliación por defecto).
 *
 * Los fixtures se crean con `Queue::fake()` DESACTIVADO todavía apagado
 * y solo se activa justo antes de correr el comando bajo prueba — crear
 * un producto ya dispara su propio intento de sincronización (Fase 6),
 * así que fakear la cola antes de eso contaría también esos despachos
 * "de fondo" y ensuciaría la aserción de lo que el COMANDO encoló.
 */
class GoogleMerchantSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_run_without_flags_only_queues_products_needing_reconciliation(): void
    {
        $upToDate = $this->eligibleProduct('Producto al día');
        $upToDate->forceFill([
            'google_merchant_status' => 'publicado',
            'google_merchant_last_sync_at' => now(),
        ])->save();

        $needsSync = $this->eligibleProduct('Producto nuevo sin sincronizar');

        Queue::fake();

        $this->artisan('google-merchant:sync --allow-non-production')->assertSuccessful();

        Queue::assertPushed(SyncProductToGoogleMerchant::class, 1);
        Queue::assertPushed(fn (SyncProductToGoogleMerchant $job) => $this->jobProductId($job) === $needsSync->id);
    }

    public function test_business_flag_scopes_to_that_business_only(): void
    {
        $productA = $this->eligibleProduct('Producto negocio A');
        $this->eligibleProduct('Producto negocio B');

        Queue::fake();

        $this->artisan("google-merchant:sync --business={$productA->business_id} --allow-non-production")->assertSuccessful();

        Queue::assertPushed(SyncProductToGoogleMerchant::class, 1);
        Queue::assertPushed(fn (SyncProductToGoogleMerchant $job) => $this->jobProductId($job) === $productA->id);
    }

    public function test_failed_flag_only_retries_products_in_error_status(): void
    {
        $errored = $this->eligibleProduct('Producto con error');
        $errored->update(['google_merchant_status' => 'error']);

        $this->eligibleProduct('Producto sano');

        Queue::fake();

        $this->artisan('google-merchant:sync --failed --allow-non-production')->assertSuccessful();

        Queue::assertPushed(SyncProductToGoogleMerchant::class, 1);
        Queue::assertPushed(fn (SyncProductToGoogleMerchant $job) => $this->jobProductId($job) === $errored->id);
    }

    public function test_all_flag_ignores_reconciliation_and_queues_everything_eligible(): void
    {
        $upToDate = $this->eligibleProduct('Producto al día');
        $upToDate->forceFill([
            'google_merchant_status' => 'publicado',
            'google_merchant_last_sync_at' => now(),
        ])->save();

        $this->eligibleProduct('Producto nuevo');

        Queue::fake();

        $this->artisan('google-merchant:sync --all --allow-non-production')->assertSuccessful();

        Queue::assertPushed(SyncProductToGoogleMerchant::class, 2);
    }

    public function test_disabled_business_is_never_queued(): void
    {
        $product = $this->eligibleProduct('Producto de negocio deshabilitado');
        $product->business->update(['google_merchant_enabled' => false]);

        Queue::fake();

        $this->artisan('google-merchant:sync --all --allow-non-production')->assertSuccessful();

        Queue::assertNotPushed(SyncProductToGoogleMerchant::class);
    }

    private function jobProductId(SyncProductToGoogleMerchant $job): int
    {
        return (fn () => $this->productId)->call($job);
    }

    private function eligibleProduct(string $name): Product
    {
        $municipality = Municipality::firstOrCreate(
            ['slug' => 'cajica'],
            ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true],
        );
        $category = Category::firstOrCreate(
            ['slug' => 'alimentos'],
            ['name' => 'Alimentos', 'is_active' => true],
        );
        $owner = User::factory()->create();
        $storefront = app(CreateStorefront::class)->handle($owner, [
            'name' => $name.' - vitrina',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ]);
        $business = $storefront->business;
        $product = app(CreateProduct::class)->handle($business, [
            'name' => $name,
            'type' => 'producto',
            'price_type' => 'exacto',
            'price' => 15000,
        ], [], $owner);
        $product->update(['status' => 'publicado']);
        $product->media()->create(['path' => 'products/test.jpg', 'position' => 0]);
        $business->update(['status' => 'publicado', 'google_merchant_enabled' => true]);

        return $product->fresh();
    }
}
