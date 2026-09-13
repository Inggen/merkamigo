<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use App\Support\GoogleMerchant\GoogleMerchantProductMapper;
use App\Support\GoogleMerchant\GoogleMerchantProductValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Escenarios 1-6 y 8-9 de la Fase 11: `GoogleMerchantProductValidator` y
 * `GoogleMerchantProductMapper`, sin tocar la red — ninguno de estos
 * casos necesita `GoogleMerchantClient`.
 */
class GoogleMerchantValidatorAndMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fully_eligible_product_passes_validation(): void
    {
        $product = $this->product();

        $this->assertSame([], app(GoogleMerchantProductValidator::class)->validate($product));
    }

    public function test_a_product_without_a_price_is_rejected(): void
    {
        $product = $this->product(['price_type' => 'consultar', 'price' => null]);

        $errors = app(GoogleMerchantProductValidator::class)->validate($product);

        $this->assertContains('El producto no tiene un precio fijo publicable (usa "Consultar" o "Sin precio").', $errors);
    }

    public function test_a_product_without_a_main_image_is_rejected(): void
    {
        $product = $this->product(withMedia: false);

        $errors = app(GoogleMerchantProductValidator::class)->validate($product);

        $this->assertContains('Falta imagen principal.', $errors);
    }

    public function test_a_handmade_product_without_gtin_is_still_eligible_and_declares_no_identifier(): void
    {
        $product = $this->product();

        $this->assertTrue(app(GoogleMerchantProductValidator::class)->isEligible($product));

        $payload = app(GoogleMerchantProductMapper::class)->apiPayload($product);

        $this->assertFalse($payload['productAttributes']['identifierExists']);
        $this->assertArrayNotHasKey('gtins', $payload['productAttributes']);
    }

    public function test_a_product_with_a_real_gtin_declares_identifier_exists_and_sends_it(): void
    {
        $product = $this->product(['gtin' => '7701234567890']);

        $payload = app(GoogleMerchantProductMapper::class)->apiPayload($product);

        $this->assertTrue($payload['productAttributes']['identifierExists']);
        $this->assertSame(['7701234567890'], $payload['productAttributes']['gtins']);
    }

    public function test_a_disabled_seller_blocks_its_products(): void
    {
        $product = $this->product();
        $product->business->update(['google_merchant_enabled' => false]);

        $errors = app(GoogleMerchantProductValidator::class)->validate($product->fresh(['business']));

        $this->assertContains('El vendedor no está habilitado para Google Shopping.', $errors);
    }

    public function test_a_sold_out_product_maps_to_out_of_stock_availability(): void
    {
        $product = $this->product(['is_available' => false]);

        $payload = app(GoogleMerchantProductMapper::class)->apiPayload($product);

        $this->assertSame('OUT_OF_STOCK', $payload['productAttributes']['availability']);
    }

    public function test_isolation_two_businesses_never_share_an_external_seller_id_or_offer_id(): void
    {
        $productA = $this->product();
        $productB = $this->product();

        $mapper = app(GoogleMerchantProductMapper::class);

        $this->assertNotSame($productA->business_id, $productB->business_id);
        $this->assertNotSame(
            $productA->business->externalSellerId(),
            $productB->business->externalSellerId(),
        );
        $this->assertNotSame($mapper->offerId($productA), $mapper->offerId($productB));

        $payloadA = $mapper->apiPayload($productA);
        $this->assertSame($productA->business->externalSellerId(), $payloadA['productAttributes']['externalSellerId']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function product(array $overrides = [], bool $withMedia = true): Product
    {
        static $counter = 0;
        $counter++;

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
            'name' => "Negocio de prueba {$counter}",
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ]);
        $business = $storefront->business;
        $business->update(['status' => 'publicado', 'google_merchant_enabled' => true]);

        $data = array_merge([
            'name' => "Producto de prueba {$counter}",
            'description' => 'Descripción de prueba para el producto.',
            'type' => 'producto',
            'price_type' => 'exacto',
            'price' => 18000,
            'is_available' => true,
        ], $overrides);

        $product = app(CreateProduct::class)->handle($business, $data, [], $owner);
        $product->update(['status' => 'publicado']);

        if ($withMedia) {
            $product->media()->create(['path' => "products/test-{$counter}.jpg", 'position' => 0]);
        }

        return $product->fresh(['business.storefront', 'business.category', 'media']);
    }
}
