<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Jobs\DeleteProductFromGoogleMerchant;
use App\Domain\Storefronts\Jobs\SyncProductToGoogleMerchant;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use App\Support\GoogleMerchant\GoogleMerchantClient;
use App\Support\GoogleMerchant\GoogleMerchantProductMapper;
use App\Support\GoogleMerchant\GoogleMerchantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Escenarios 7, 9, 10, 11 y 13 de la Fase 11 — todos con
 * `GoogleMerchantClient` atado a un resolver de token FALSO (nunca el
 * real, resuelto por el contenedor con las credenciales de este
 * entorno) y `Http::fake()`, para no llamar nunca a Google de verdad.
 */
class GoogleMerchantServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(GoogleMerchantClient::class, fn ($app) => new GoogleMerchantClient(
            $app->make(GoogleMerchantProductMapper::class),
            fn (): string => 'test-access-token',
        ));

        config()->set('services.google_merchant.enabled', true);
        config()->set('services.google_merchant.account_id', '123456');
        config()->set('services.google_merchant.data_source_id', '789');
        config()->set('services.google_merchant.endpoint', 'https://merchantapi.googleapis.com');
    }

    public function test_price_update_triggers_a_resync(): void
    {
        Http::fake(['*productInputs:insert*' => Http::response(['name' => 'x'], 200)]);

        $product = $this->product();
        $service = app(GoogleMerchantService::class);

        $service->syncProduct($product);
        $this->assertSame('publicado', $product->fresh()->google_merchant_status);

        $product->update(['price' => 99000]);
        $service->syncProduct($product->fresh());

        Http::assertSentCount(2);
    }

    public function test_unchanged_product_does_not_call_google_again(): void
    {
        Http::fake(['*productInputs:insert*' => Http::response(['name' => 'x'], 200)]);

        $product = $this->product();
        $service = app(GoogleMerchantService::class);

        $service->syncProduct($product);
        $service->syncProduct($product->fresh());

        Http::assertSentCount(1);
    }

    public function test_a_previously_published_product_that_becomes_ineligible_is_deleted_from_google(): void
    {
        Http::fake([
            '*productInputs:insert*' => Http::response(['name' => 'x'], 200),
            '*productInputs/*' => Http::response([], 200),
        ]);

        $product = $this->product();
        $service = app(GoogleMerchantService::class);

        $service->syncProduct($product);
        $this->assertSame('publicado', $product->fresh()->google_merchant_status);

        $product->update(['status' => 'archivado']);
        $service->syncProduct($product->fresh());

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE');
        $this->assertSame('no_publicado', $product->fresh()->google_merchant_status);
    }

    public function test_a_temporary_google_error_is_rethrown_and_does_not_mark_the_product_as_failed(): void
    {
        Http::fake(['*productInputs:insert*' => Http::response(['error' => ['message' => 'backend error']], 503)]);

        $product = $this->product();

        $this->expectException(RequestException::class);

        try {
            app(GoogleMerchantService::class)->syncProduct($product);
        } finally {
            $this->assertSame('no_publicado', $product->fresh()->google_merchant_status);
        }
    }

    public function test_a_permanent_google_error_is_recorded_without_rethrowing(): void
    {
        Http::fake(['*productInputs:insert*' => Http::response(['error' => ['message' => 'invalidArgument productAttributes.price']], 400)]);

        $product = $this->product();

        app(GoogleMerchantService::class)->syncProduct($product);

        $product->refresh();
        $this->assertSame('error', $product->google_merchant_status);
        $this->assertStringContainsString('price', $product->google_merchant_last_error);
    }

    public function test_jobs_are_configured_to_retry_with_backoff(): void
    {
        $syncJob = new SyncProductToGoogleMerchant(1);
        $deleteJob = new DeleteProductFromGoogleMerchant(1);

        $this->assertSame(5, $syncJob->tries);
        $this->assertSame(5, $deleteJob->tries);
        $this->assertNotEmpty($syncJob->backoff());
        $this->assertNotEmpty($deleteJob->backoff());
    }

    private function product(): Product
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
            'name' => 'Negocio de servicio '.uniqid(),
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ]);
        $business = $storefront->business;
        $business->update(['status' => 'publicado', 'google_merchant_enabled' => true]);

        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Producto de servicio',
            'description' => 'Descripción de prueba.',
            'type' => 'producto',
            'price_type' => 'exacto',
            'price' => 18000,
        ], [], $owner);
        $product->update(['status' => 'publicado']);
        $product->media()->create(['path' => 'products/test.jpg', 'position' => 0]);

        return $product->fresh(['business.storefront', 'business.category', 'media']);
    }
}
