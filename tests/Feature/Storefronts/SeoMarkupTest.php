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

    /**
     * Bug real reportado por el usuario en iPhone/Safari: los íconos de
     * redes sociales de la vitrina pública se veían invisibles (el círculo
     * de fondo sí, el ícono no) porque el `<svg>` no traía su propio
     * `width`/`height` — solo el `<span>` que lo envolvía tenía el tamaño
     * por CSS. Safari no le da tamaño por defecto a un SVG así, a
     * diferencia de otros navegadores.
     */
    public function test_the_storefront_renders_social_icons_with_an_explicit_svg_size(): void
    {
        [$business] = $this->publishedBusinessWithProduct('producto');
        $business->update(['social_links' => [
            'facebook' => 'https://facebook.com/panaderiapublica',
            'instagram' => 'https://instagram.com/panaderiapublica',
        ]]);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee('Síguenos en redes')
            ->assertSee('<svg width="100%" height="100%"', false);
    }

    /**
     * Bug real reportado por el usuario: en iPhone/Safari, los enlaces de
     * "Comparte este producto" (wa.me, sharer.php, instagram.com) suelen
     * ser interceptados por la app correspondiente y abren su feed normal
     * en vez del compositor de compartir — o, en el caso de Instagram, ni
     * siquiera había una URL de compartir real. El Web Share API nativo
     * (`navigator.share`) delega en el propio sistema operativo; la fila de
     * íconos por red sigue existiendo como respaldo para navegadores sin
     * soporte (`shareSupported` se evalúa en el cliente, así que ambos
     * bloques quedan en el HTML — Alpine decide cuál mostrar).
     */
    public function test_the_product_page_renders_both_the_native_share_button_and_the_per_network_fallback(): void
    {
        [$business, $product] = $this->publishedBusinessWithProduct('producto');

        $this->get(route('vitrinas.product', [$business, $product]))
            ->assertOk()
            ->assertSee('navigator.share', false)
            ->assertSee('shareSupported', false)
            ->assertSee('wa.me', false);
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
