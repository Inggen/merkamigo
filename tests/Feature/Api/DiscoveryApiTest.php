<?php

namespace Tests\Feature\Api;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 5.1 del TODO: descubrimiento público (municipios, categorías, Plaza,
 * negocio y productos publicados) vía `/api/v1`.
 */
class DiscoveryApiTest extends TestCase
{
    use AssertsApiEnvelope;
    use RefreshDatabase;

    private function publishedBusiness(Municipality $municipality, Category $category, string $businessName = 'Panadería de Cajicá', string $productName = 'Pan francés'): array
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => $businessName,
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        $product = app(CreateProduct::class)->handle($business, [
            'name' => $productName, 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        $product->update(['status' => 'publicado']);
        app(PublishStorefront::class)->handle($business, $owner);

        return [$owner, $business->fresh(), $product->fresh()];
    }

    public function test_municipios_and_categorias_are_public(): void
    {
        Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $this->getJson(route('api.v1.municipios'))->assertOk()->assertJsonPath('data.0.slug', 'cajica');
        $this->getJson(route('api.v1.categorias'))->assertOk()->assertJsonPath('data.0.slug', 'alimentos');
    }

    public function test_the_public_lists_are_cached_but_invalidated_on_change(): void
    {
        $this->getJson(route('api.v1.municipios'))->assertOk()->assertJsonCount(0, 'data');

        Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $this->getJson(route('api.v1.municipios'))->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_plaza_lists_published_businesses_matching_filters(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        [, $business] = $this->publishedBusiness($municipality, $category);

        $response = $this->getJson(route('api.v1.plaza', ['municipio' => 'cajica']));

        $this->assertPaginatedEnvelope($response)
            ->assertOk()
            ->assertJsonPath('data.0.name', $business->name);
    }

    public function test_plaza_negocio_show_is_public_and_hides_unpublished_businesses(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        [, $business] = $this->publishedBusiness($municipality, $category);

        $this->getJson(route('api.v1.plaza.negocios.show', $business->slug))
            ->assertOk()
            ->assertJsonPath('data.name', $business->name)
            ->assertJsonMissing(['members']);

        $draft = app(CreateStorefront::class)->handle(User::factory()->create(), ['name' => 'Borrador'])->business;

        $this->getJson(route('api.v1.plaza.negocios.show', $draft->slug))->assertNotFound();
    }

    public function test_products_are_listed_and_shown_publicly_scoped_to_their_business(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        [, $business, $product] = $this->publishedBusiness($municipality, $category);

        $this->getJson(route('api.v1.plaza.negocios.productos', $business->slug))
            ->assertOk()
            ->assertJsonPath('data.0.name', $product->name);

        $this->getJson(route('api.v1.plaza.negocios.productos.show', [$business->slug, $product->slug]))
            ->assertOk()
            ->assertJsonPath('data.name', $product->name);
    }

    public function test_a_products_slug_from_another_business_is_not_found(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        [, $businessA, $productA] = $this->publishedBusiness($municipality, $category, 'Negocio A', 'Pan francés');
        [, $businessB] = $this->publishedBusiness($municipality, $category, 'Negocio B', 'Torta de chocolate');

        $this->getJson(route('api.v1.plaza.negocios.productos.show', [$businessB->slug, $productA->slug]))
            ->assertNotFound();
    }
}
