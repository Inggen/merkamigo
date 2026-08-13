<?php

namespace Tests\Feature\Api;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\StandAssignment;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Trust\Models\Recommendation;
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

    /**
     * IMM-033: el modal de vitrina de la plaza inmersiva necesita un
     * resumen de reseñas. No hay calificación numérica en este codebase
     * — las "reseñas" son `Recommendation` (texto libre).
     */
    public function test_plaza_negocio_show_exposes_a_recommendations_summary(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        [, $business] = $this->publishedBusiness($municipality, $category);

        Recommendation::create([
            'business_id' => $business->id,
            'status' => Recommendation::PUBLICADA,
            'body' => 'Excelente atención y productos frescos.',
        ]);
        Recommendation::create([
            'business_id' => $business->id,
            'status' => 'pendiente',
            'body' => 'Reseña todavía sin moderar, no debe contar.',
        ]);

        $this->getJson(route('api.v1.plaza.negocios.show', $business->slug))
            ->assertOk()
            ->assertJsonPath('data.recommendations_summary.count', 1)
            ->assertJsonPath('data.recommendations_summary.recent.0.body', 'Excelente atención y productos frescos.');
    }

    /**
     * IMM-034: el panel de búsqueda de la plaza inmersiva necesita saber
     * en qué plaza está un negocio para ofrecer "Ver aquí" o "Viajar a...".
     */
    public function test_plaza_negocio_show_exposes_its_immersive_location_when_it_has_a_live_stand(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        [, $business] = $this->publishedBusiness($municipality, $category);

        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Parque Cajicá',
            'slug' => 'parque-cajica',
            'route_name' => 'labs.generic-plaza',
        ]);
        $plaza = $experience->plazas()->create([
            'name' => 'Plaza principal',
            'order' => 1,
            'status' => 'activa',
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);
        $experience->update(['status' => 'publicada']);
        $zone = $plaza->zones()->create([
            'name' => 'Zona única',
            'polygon' => ['points' => [
                ['x' => -20, 'z' => -20], ['x' => 20, 'z' => -20], ['x' => 20, 'z' => 20], ['x' => -20, 'z' => 20],
            ]],
        ]);
        $slot = $zone->slots()->create([
            'code' => 'S1',
            'world_position' => ['x' => 0, 'y' => 0, 'z' => 0],
            'max_width' => 4,
            'max_depth' => 4,
        ]);
        $template = ImmersiveObjectTemplate::create([
            'name' => 'Caseta', 'slug' => 'caseta-'.uniqid(), 'category' => 'stand', 'builder_key' => 'standBooth',
            'max_width' => 4, 'max_depth' => 4, 'max_height' => 3, 'status' => 'publicada',
        ]);
        // `CreateStorefront` (usado por `publishedBusiness()`) ya crea un
        // `StandAssignment` propio para el negocio (onboarding) —
        // `updateOrCreate` evita chocar con la unicidad de `business_id`.
        StandAssignment::updateOrCreate(['business_id' => $business->id], [
            'immersive_plaza_id' => $plaza->id,
            'stand_slot_id' => $slot->id,
            'object_template_id' => $template->id,
            'status' => 'publicado',
        ]);

        $this->getJson(route('api.v1.plaza.negocios.show', $business->slug))
            ->assertOk()
            ->assertJsonPath('data.immersive_location.plaza_name', 'Plaza principal')
            ->assertJsonPath('data.immersive_location.municipality_slug', 'cajica')
            ->assertJsonPath('data.immersive_location.travel_url', route('labs.generic-plaza', ['municipio' => 'cajica']));
    }

    public function test_plaza_negocio_show_immersive_location_is_null_without_a_live_stand(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        [, $business] = $this->publishedBusiness($municipality, $category);

        $this->getJson(route('api.v1.plaza.negocios.show', $business->slug))
            ->assertOk()
            ->assertJsonPath('data.immersive_location', null);
    }

    /**
     * IMM-034: "selector de plaza" — el panel solo debe ofrecer viajar a
     * municipios con una experiencia inmersiva realmente publicada.
     */
    public function test_the_municipios_endpoint_exposes_an_immersive_lab_url_only_when_published(): void
    {
        $withExperience = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $withExperience->id,
            'name' => 'Parque Cajicá',
            'slug' => 'parque-cajica',
            'route_name' => 'labs.generic-plaza',
        ]);
        $experience->plazas()->create([
            'name' => 'Plaza principal',
            'order' => 1,
            'status' => 'activa',
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);
        $experience->update(['status' => 'publicada']);
        Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);

        $response = $this->getJson(route('api.v1.municipios'))->assertOk();
        $bySlug = array_column($response->json('data'), null, 'slug');

        $this->assertSame(route('labs.generic-plaza', ['municipio' => 'cajica']), $bySlug['cajica']['immersive_lab_url']);
        $this->assertNull($bySlug['zipaquira']['immersive_lab_url']);
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
