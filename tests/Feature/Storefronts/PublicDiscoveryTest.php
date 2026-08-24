<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Businesses\Models\Business;
use App\Domain\Businesses\Models\BusinessAttribute;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 1.3 y 1.5 del TODO: vitrina pública y plaza del municipio.
 */
class PublicDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(Municipality $municipality, Category $category): Business
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Pública',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos todos los días.',
        ])->business;

        $business->update(['logo_path' => 'businesses/1/logo.jpg']);

        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Pan francés', 'type' => 'producto', 'price_type' => 'exacto', 'price' => 2000,
        ], [], $owner);
        $product->update(['status' => 'publicado']);

        app(PublishStorefront::class)->handle($business, $owner);

        return $business->fresh();
    }

    public function test_a_draft_business_does_not_appear_in_the_plaza_or_the_public_page(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $owner = User::factory()->create();
        $draft = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Borrador',
            'municipality_id' => $municipality->id,
        ])->business;

        $this->get(route('plaza.show', $municipality))
            ->assertOk()
            ->assertDontSee('Negocio Borrador');

        $this->get(route('vitrinas.show', $draft))->assertNotFound();
    }

    public function test_a_published_business_appears_in_its_municipality_plaza_and_public_page(): void
    {
        $municipality = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);
        $otherMunicipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $business = $this->publishedBusiness($municipality, $category);

        $this->get(route('plaza.show', $municipality))
            ->assertOk()
            ->assertSee($business->name);

        $this->get(route('plaza.show', $otherMunicipality))
            ->assertOk()
            ->assertDontSee($business->name);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee($business->name)
            ->assertSee('Pan francés');
    }

    public function test_the_qr_endpoint_returns_a_png_image_for_a_published_business(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $business = $this->publishedBusiness($municipality, $category);

        $response = $this->get(route('vitrinas.qr', $business));

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }

    public function test_filtering_the_plaza_by_category_does_not_error(): void
    {
        // Regresión: la ruta plaza.category tiene dos parámetros de modelo
        // ({municipio}/categorias/{categoria}) y Laravel intentaba aplicar
        // scoping anidado automático buscando un método
        // Municipality::categorias(), que no existe — la ruta debe declarar
        // withoutScopedBindings() ya que una categoría no es un recurso hijo
        // de un municipio.
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $business = $this->publishedBusiness($municipality, $category);

        $this->get(route('plaza.category', [$municipality, $category]))
            ->assertOk()
            ->assertSee($business->name);
    }

    public function test_search_only_returns_published_businesses_matching_the_name(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $business = $this->publishedBusiness($municipality, $category);

        $this->get(route('buscar', ['q' => 'Panadería']))
            ->assertOk()
            ->assertSee($business->name);

        $this->get(route('buscar', ['q' => 'Ferretería']))
            ->assertOk()
            ->assertDontSee($business->name);
    }

    public function test_the_public_category_page_lists_municipalities_with_offer_and_links_to_their_local_plaza(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $otherMunicipality = Municipality::create(['name' => 'Tabio', 'slug' => 'tabio', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $this->publishedBusiness($municipality, $category);

        $this->get(route('categorias.show', $category))
            ->assertOk()
            ->assertSee($category->name)
            ->assertSee($municipality->name)
            ->assertDontSee($otherMunicipality->name)
            ->assertSee(route('plaza.category', [$municipality, $category]), false);
    }

    public function test_the_public_product_page_reflects_promo_price_variants_and_sold_out(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $business = $this->publishedBusiness($municipality, $category);
        $product = $business->products()->firstOrFail();

        $product->update([
            'promo_price' => 1500,
            'promo_label' => 'Oferta del día',
        ]);
        $product->variants()->create(['label' => 'Porción grande', 'price' => 3000, 'position' => 0]);

        $this->get(route('vitrinas.product', [$business, $product]))
            ->assertOk()
            ->assertSee('Oferta del día')
            ->assertSee('Porción grande')
            ->assertSee(__('Disponible'));

        $product->update(['is_available' => false]);

        $this->get(route('vitrinas.product', [$business, $product]))
            ->assertOk()
            ->assertSee(__('Agotado'))
            ->assertSee(__('Consultar disponibilidad'));
    }

    public function test_the_vitrina_shows_active_attributes_and_an_aggregated_photo_gallery(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $business = $this->publishedBusiness($municipality, $category);
        $attribute = BusinessAttribute::create(['name' => 'Producto artesanal', 'slug' => 'producto-artesanal', 'is_active' => true]);
        $business->update(['attributes' => [$attribute->slug]]);

        $product = $business->products()->firstOrFail();
        $product->media()->createMany([
            ['path' => 'products/1/a.jpg', 'position' => 0],
            ['path' => 'products/1/b.jpg', 'position' => 1],
        ]);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee('Producto artesanal')
            ->assertSee(__('Galería'));
    }

    public function test_an_attribute_with_a_valid_icon_and_description_shows_both(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $business = $this->publishedBusiness($municipality, $category);
        $attribute = BusinessAttribute::create([
            'name' => 'Acepta pagos digitales',
            'slug' => 'acepta-pagos-digitales',
            'icon' => 'credit-card',
            'description' => 'Paga fácil y seguro con tus medios digitales favoritos.',
            'is_active' => true,
        ]);
        $business->update(['attributes' => [$attribute->slug]]);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee('Acepta pagos digitales')
            ->assertSee('Paga fácil y seguro con tus medios digitales favoritos.');
    }

    public function test_an_attribute_with_an_invalid_icon_name_does_not_break_the_page(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $business = $this->publishedBusiness($municipality, $category);
        $attribute = BusinessAttribute::create([
            'name' => 'Atributo con ícono inválido',
            'slug' => 'atributo-icono-invalido',
            'icon' => 'este-icono-no-existe',
            'is_active' => true,
        ]);
        $business->update(['attributes' => [$attribute->slug]]);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee('Atributo con ícono inválido');
    }
}
