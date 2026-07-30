<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_storefront_renders_structured_data_for_the_business_and_breadcrumbs(): void
    {
        [$business] = $this->publishedBusinessWithProduct('producto');

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee('"@type":"Store"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee(route('vitrinas.show', $business), false);
    }

    public function test_the_public_product_page_renders_product_or_service_schema(): void
    {
        [$productBusiness, $product] = $this->publishedBusinessWithProduct('producto');
        [$serviceBusiness, $service] = $this->publishedBusinessWithProduct('servicio');

        $this->get(route('vitrinas.product', [$productBusiness, $product]))
            ->assertOk()
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"priceCurrency":"COP"', false);

        $this->get(route('vitrinas.product', [$serviceBusiness, $service]))
            ->assertOk()
            ->assertSee('"@type":"Service"', false);
    }

    public function test_the_faq_page_renders_faq_schema_and_search_page_is_noindex(): void
    {
        $this->get(route('preguntas-frecuentes'))
            ->assertOk()
            ->assertSee('"@type":"FAQPage"', false);

        $this->get(route('buscar', ['q' => 'pan']))
            ->assertOk()
            ->assertSee('noindex,follow');
    }

    /**
     * @return array{0: Business, 1: Product}
     */
    private function publishedBusinessWithProduct(string $type): array
    {
        $municipality = Municipality::create([
            'name' => 'Cajicá '.strtoupper($type),
            'slug' => 'cajica-'.$type,
            'department' => 'Cundinamarca',
            'is_active' => true,
        ]);
        $category = Category::create([
            'name' => 'Alimentos '.strtoupper($type),
            'slug' => 'alimentos-'.$type,
            'is_active' => true,
        ]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Pública '.strtoupper($type),
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos todos los días.',
        ])->business;

        $business->update(['logo_path' => 'businesses/'.$business->id.'/logo.jpg']);

        $product = app(CreateProduct::class)->handle($business, [
            'name' => $type === 'servicio' ? 'Servicio de catering' : 'Pan francés',
            'type' => $type,
            'price_type' => 'exacto',
            'price' => 2000,
            'description' => 'Producto o servicio destacado.',
        ], [], $owner);
        $product->update(['status' => 'publicado']);
        $product->media()->create(['path' => 'products/'.$product->id.'/image.jpg', 'position' => 0]);

        app(PublishStorefront::class)->handle($business, $owner);

        return [$business->fresh(['storefront', 'municipality', 'category']), $product->fresh(['media', 'variants'])];
    }
}
