<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use App\Support\GoogleMerchant\GoogleMerchantClient;
use App\Support\GoogleMerchant\GoogleMerchantProductMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleMerchantIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_feed_returns_valid_xml(): void
    {
        $this->publishedProduct();

        $response = $this->get(route('feeds.google-merchant'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->assertNotFalse(simplexml_load_string($response->getContent()));
        $response->assertSee('<g:id>MKG-', false);
    }

    public function test_client_inserts_an_eligible_product_in_the_configured_api_source(): void
    {
        $product = $this->publishedProduct();
        $product->update([
            'promo_price' => 10000,
            'promo_starts_at' => now()->subDay(),
            'promo_ends_at' => now()->addDay(),
        ]);

        config()->set('services.google_merchant', [
            ...config('services.google_merchant'),
            'account_id' => '123456',
            'data_source_id' => '789',
            'endpoint' => 'https://merchantapi.googleapis.com',
            'content_language' => 'es',
            'feed_label' => 'CO',
            'currency' => 'COP',
            'timeout' => 30,
        ]);

        Http::fake([
            'merchantapi.googleapis.com/*' => Http::response(['name' => 'accounts/123456/productInputs/test'], 200),
        ]);

        $client = new GoogleMerchantClient(
            app(GoogleMerchantProductMapper::class),
            fn (): string => 'test-access-token',
        );

        $client->insert($product->load(['business.category', 'business.storefront', 'media']));

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'POST'
                && str_starts_with($request->url(), 'https://merchantapi.googleapis.com/products/v1/accounts/123456/productInputs:insert')
                && ($query['dataSource'] ?? null) === 'accounts/123456/dataSources/789'
                && $request->hasHeader('Authorization', 'Bearer test-access-token')
                && $request['offerId'] !== null
                && $request['contentLanguage'] === 'es'
                && $request['feedLabel'] === 'CO'
                && $request['productAttributes']['availability'] === 'IN_STOCK'
                && $request['productAttributes']['price'] === [
                    'amountMicros' => '12500000000',
                    'currencyCode' => 'COP',
                ]
                && $request['productAttributes']['salePrice'] === [
                    'amountMicros' => '10000000000',
                    'currencyCode' => 'COP',
                ]
                && isset($request['productAttributes']['salePriceEffectiveDate']['startTime'])
                && isset($request['productAttributes']['salePriceEffectiveDate']['endTime']);
        });
    }

    public function test_dry_run_validates_products_without_credentials_or_http_requests(): void
    {
        $this->publishedProduct();
        Http::fake();

        $this->artisan('google-merchant:sync --dry-run')
            ->expectsOutput('Productos válidos: 1; omitidos: 0; fallidos: 0.')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_real_sync_is_blocked_outside_production(): void
    {
        Http::fake();

        $this->artisan('google-merchant:sync')
            ->expectsOutput('Por seguridad, los envíos reales solo se permiten en producción. Usa --dry-run para validar.')
            ->assertFailed();

        Http::assertNothingSent();
    }

    private function publishedProduct(): Product
    {
        $municipality = Municipality::create([
            'name' => 'Cajicá',
            'slug' => 'cajica',
            'department' => 'Cundinamarca',
            'is_active' => true,
        ]);
        $category = Category::create([
            'name' => 'Alimentos',
            'slug' => 'alimentos',
            'is_active' => true,
        ]);
        $owner = User::factory()->create();
        $storefront = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Pública',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos todos los días.',
        ]);
        $business = $storefront->business;
        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Pan artesanal',
            'type' => 'producto',
            'price_type' => 'exacto',
            'price' => 12500,
        ], [], $owner);
        $product->update(['status' => 'publicado']);
        $product->media()->create(['path' => 'products/pan.jpg', 'position' => 0]);
        $business->update(['status' => 'publicado']);

        return $product->fresh();
    }
}
