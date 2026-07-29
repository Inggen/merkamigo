<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 1.5 del TODO: destacados/nuevos, filtro por zona, listado de productos
 * y portada administrable por municipio en la Plaza.
 */
class PlazaFiltersTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(Municipality $municipality, Category $category, array $overrides = []): Business
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio '.uniqid(),
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Descripción de prueba.',
        ])->business;

        $business->update(array_merge(['logo_path' => 'businesses/1/logo.jpg'], $overrides));

        $product = app(CreateProduct::class)->handle($business, [
            'name' => 'Producto de prueba '.uniqid(), 'type' => 'producto', 'price_type' => 'exacto', 'price' => 2000,
        ], [], $owner);
        $product->update(['status' => 'publicado']);

        app(PublishStorefront::class)->handle($business, $owner);

        return $business->fresh();
    }

    public function test_a_featured_business_appears_in_destacados_and_not_duplicated_in_nuevos(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $featured = $this->publishedBusiness($municipality, $category);
        $featured->update(['featured_until' => now()->addDays(7)]);

        $regular = $this->publishedBusiness($municipality, $category);

        $response = $this->get(route('plaza.show', $municipality));

        $response->assertOk()->assertSeeInOrder([
            __('Destacados'),
            $featured->name,
            __('Nuevos'),
            $regular->name,
        ]);
    }

    public function test_filtering_the_plaza_by_zone_only_returns_matching_businesses(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $centro = $this->publishedBusiness($municipality, $category, ['zone' => 'Centro']);
        $norte = $this->publishedBusiness($municipality, $category, ['zone' => 'Norte']);

        $response = $this->get(route('plaza.show', $municipality).'?zona=Centro');

        $response->assertOk()
            ->assertSee($centro->name)
            ->assertDontSee($norte->name);
    }

    public function test_published_products_from_the_municipality_appear_in_the_plaza_and_respect_the_available_filter(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $business = $this->publishedBusiness($municipality, $category);
        $product = $business->products()->firstOrFail();

        $this->get(route('plaza.show', $municipality))
            ->assertOk()
            ->assertSee(__('Productos'))
            ->assertSee($product->name);

        $product->update(['is_available' => false]);

        $this->get(route('plaza.show', $municipality).'?disponibles=1')
            ->assertOk()
            ->assertDontSee($product->name);
    }

    public function test_the_plaza_hero_shows_the_municipality_cover_when_set_and_falls_back_otherwise(): void
    {
        $withCover = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true, 'cover_path' => 'municipalities/cajica.jpg']);
        $withoutCover = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);

        $this->get(route('plaza.show', $withCover))
            ->assertOk()
            ->assertSee($withCover->coverUrl(), false);

        $this->get(route('plaza.show', $withoutCover))
            ->assertOk()
            ->assertSee(asset('images/backgrounds/fondo-buscador-principal.webp'), false);
    }
}
