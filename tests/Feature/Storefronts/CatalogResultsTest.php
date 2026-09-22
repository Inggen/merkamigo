<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Livewire\CatalogResults;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_pages_update_inside_the_livewire_component(): void
    {
        [$municipality, $category] = $this->catalogContext();
        $older = $this->publishedBusiness($municipality, $category, 'Vitrina anterior', 'Producto anterior');
        $newer = $this->publishedBusiness($municipality, $category, 'Vitrina reciente', 'Producto reciente');

        $older->forceFill(['created_at' => now()->subDay()])->save();
        $newer->forceFill(['created_at' => now()])->save();

        Livewire::test(CatalogResults::class, [
            'type' => 'businesses',
            'municipalityId' => $municipality->id,
            'perPage' => 1,
        ])
            ->assertSee('Vitrina reciente')
            ->assertDontSee('Vitrina anterior')
            ->assertSee(__('Mostrando'))
            ->call('nextPage', 'page')
            ->assertSee('Vitrina anterior')
            ->assertDontSee('Vitrina reciente');
    }

    public function test_product_pages_update_inside_the_livewire_component(): void
    {
        [$municipality, $category] = $this->catalogContext();
        $business = $this->publishedBusiness($municipality, $category, 'Vitrina', 'Producto anterior');
        $older = $business->products()->firstOrFail();
        $newer = app(CreateProduct::class)->handle($business, [
            'name' => 'Producto reciente',
            'type' => 'producto',
            'price_type' => 'exacto',
            'price' => 3000,
        ], [], $business->organization->owner);

        $older->forceFill(['status' => 'publicado', 'created_at' => now()->subDay()])->save();
        $newer->forceFill(['status' => 'publicado', 'created_at' => now()])->save();

        Livewire::test(CatalogResults::class, [
            'type' => 'products',
            'municipalityId' => $municipality->id,
            'perPage' => 1,
        ])
            ->assertSee('Producto reciente')
            ->assertDontSee('Producto anterior')
            ->assertSee(__('Mostrando'))
            ->call('nextPage', 'productos_page')
            ->assertSee('Producto anterior')
            ->assertDontSee('Producto reciente');
    }

    /**
     * 1.3 del TODO social: filtro de precio del buscador.
     */
    public function test_products_can_be_filtered_by_a_price_range(): void
    {
        [$municipality, $category] = $this->catalogContext();
        $business = $this->publishedBusiness($municipality, $category, 'Vitrina', 'Producto económico');
        $cheap = $business->products()->firstOrFail();
        $expensive = app(CreateProduct::class)->handle($business, [
            'name' => 'Producto premium',
            'type' => 'producto',
            'price_type' => 'exacto',
            'price' => 90000,
        ], [], $business->organization->owner);
        $expensive->update(['status' => 'publicado']);

        Livewire::test(CatalogResults::class, [
            'type' => 'products',
            'municipalityId' => $municipality->id,
            'minPrice' => 50000,
        ])
            ->assertSee('Producto premium')
            ->assertDontSee('Producto económico');

        Livewire::test(CatalogResults::class, [
            'type' => 'products',
            'municipalityId' => $municipality->id,
            'maxPrice' => 50000,
        ])
            ->assertSee('Producto económico')
            ->assertDontSee('Producto premium');
    }

    /**
     * Fase 5.1 del TODO social: el radio de "Cerca de mí" SÍ filtra
     * (excluye), a diferencia del propio botón "Cerca de mí" que solo
     * ordena. Coordenadas de referencia: Bogotá (4.711, -74.0721);
     * `$near` cerca (~1.1 km) y `$far` lejos (~44 km, Zipaquirá).
     */
    public function test_a_radius_excludes_businesses_beyond_it(): void
    {
        [$municipality, $category] = $this->catalogContext();
        $near = $this->publishedBusiness($municipality, $category, 'Negocio cercano', 'Producto cercano');
        $near->update(['latitude' => 4.7210, 'longitude' => -74.0721]);
        $far = $this->publishedBusiness($municipality, $category, 'Negocio lejano', 'Producto lejano');
        $far->update(['latitude' => 5.0269, 'longitude' => -74.0034]);

        Livewire::test(CatalogResults::class, [
            'type' => 'businesses',
            'municipalityId' => $municipality->id,
            'latitude' => 4.7110,
            'longitude' => -74.0721,
            'radiusKm' => 5,
        ])
            ->assertSee('Negocio cercano')
            ->assertDontSee('Negocio lejano');
    }

    public function test_a_business_without_coordinates_is_excluded_once_a_radius_is_set(): void
    {
        [$municipality, $category] = $this->catalogContext();
        $withCoordinates = $this->publishedBusiness($municipality, $category, 'Con coordenadas', 'Producto A');
        $withCoordinates->update(['latitude' => 4.7110, 'longitude' => -74.0721]);
        $this->publishedBusiness($municipality, $category, 'Sin coordenadas', 'Producto B');

        Livewire::test(CatalogResults::class, [
            'type' => 'businesses',
            'municipalityId' => $municipality->id,
            'latitude' => 4.7110,
            'longitude' => -74.0721,
            'radiusKm' => 5,
        ])
            ->assertSee('Con coordenadas')
            ->assertDontSee('Sin coordenadas');
    }

    public function test_a_radius_excludes_products_from_businesses_beyond_it(): void
    {
        [$municipality, $category] = $this->catalogContext();
        $near = $this->publishedBusiness($municipality, $category, 'Negocio cercano', 'Producto cercano');
        $near->update(['latitude' => 4.7210, 'longitude' => -74.0721]);
        $far = $this->publishedBusiness($municipality, $category, 'Negocio lejano', 'Producto lejano');
        $far->update(['latitude' => 5.0269, 'longitude' => -74.0034]);

        Livewire::test(CatalogResults::class, [
            'type' => 'products',
            'municipalityId' => $municipality->id,
            'latitude' => 4.7110,
            'longitude' => -74.0721,
            'radiusKm' => 5,
        ])
            ->assertSee('Producto cercano')
            ->assertDontSee('Producto lejano');
    }

    public function test_without_a_radius_distance_only_orders_and_never_excludes(): void
    {
        [$municipality, $category] = $this->catalogContext();
        $near = $this->publishedBusiness($municipality, $category, 'Negocio cercano', 'Producto cercano');
        $near->update(['latitude' => 4.7210, 'longitude' => -74.0721]);
        $far = $this->publishedBusiness($municipality, $category, 'Negocio lejano', 'Producto lejano');
        $far->update(['latitude' => 5.0269, 'longitude' => -74.0034]);

        Livewire::test(CatalogResults::class, [
            'type' => 'businesses',
            'municipalityId' => $municipality->id,
            'latitude' => 4.7110,
            'longitude' => -74.0721,
        ])
            ->assertSee('Negocio cercano')
            ->assertSee('Negocio lejano');
    }

    /**
     * @return array{Municipality, Category}
     */
    private function catalogContext(): array
    {
        app()->setLocale('es');

        return [
            Municipality::create([
                'name' => 'Cajicá',
                'slug' => 'cajica',
                'department' => 'Cundinamarca',
                'is_active' => true,
            ]),
            Category::create([
                'name' => 'Alimentos',
                'slug' => 'alimentos',
                'is_active' => true,
            ]),
        ];
    }

    private function publishedBusiness(
        Municipality $municipality,
        Category $category,
        string $businessName,
        string $productName,
    ): Business {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => $businessName,
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Descripción de prueba.',
        ])->business;

        $business->update(['logo_path' => 'businesses/1/logo.jpg']);

        $product = app(CreateProduct::class)->handle($business, [
            'name' => $productName,
            'type' => 'producto',
            'price_type' => 'exacto',
            'price' => 2000,
        ], [], $owner);
        $product->update(['status' => 'publicado']);

        app(PublishStorefront::class)->handle($business, $owner);

        return $business->fresh();
    }
}
