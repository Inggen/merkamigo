<?php

namespace Tests\Feature\Immersive;

use App\Domain\Analytics\Models\ImmersiveEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IMM-043: telemetría de navegación de la plaza inmersiva — mismo
 * comportamiento de deduplicación/filtro de bots que `AnalyticsTest`
 * (hermano de esta suite, `RegisterAnalyticsEvent`), aplicado a
 * `RegisterImmersiveEvent`/`immersive_events`.
 */
class ImmersivePlazaEventsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function makePlaza(): ImmersivePlaza
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica-'.uniqid()]);
        $experience = ImmersiveExperience::create([
            'municipality_id' => $municipality->id,
            'name' => 'Plaza de prueba',
            'slug' => 'plaza-'.uniqid(),
            'route_name' => 'labs.generic-plaza',
        ]);
        $plaza = $experience->plazas()->create([
            'name' => 'Plaza 1',
            'order' => 1,
            'status' => 'activa',
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);

        return $plaza;
    }

    private function publishedBusiness(): Business
    {
        $suffix = uniqid();
        $municipality = Municipality::firstOrCreate(['slug' => 'cajica-negocio'], ['name' => 'Cajicá', 'is_active' => true]);
        $category = Category::firstOrCreate(['slug' => 'alimentos'], ['name' => 'Alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Medida '.$suffix,
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ])->business;
        app(CreateProduct::class)->handle($business, [
            'name' => 'Pan', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);

        return $business->fresh(['products']);
    }

    public function test_it_registers_a_plaza_entry_event(): void
    {
        $plaza = $this->makePlaza();

        $this->postJson("/api/v1/inmersivo/plazas/{$plaza->id}/eventos", [
            'type' => ImmersiveEvent::PLAZA_ENTRY,
        ])->assertStatus(202);

        $this->assertDatabaseHas('immersive_events', [
            'immersive_plaza_id' => $plaza->id,
            'type' => ImmersiveEvent::PLAZA_ENTRY,
            'business_id' => null,
            'subject_type' => null,
        ]);
    }

    public function test_it_registers_a_vitrina_opened_event_scoped_to_a_business(): void
    {
        $plaza = $this->makePlaza();
        $business = $this->publishedBusiness();

        $this->postJson("/api/v1/inmersivo/plazas/{$plaza->id}/eventos", [
            'type' => ImmersiveEvent::VITRINA_OPENED,
            'business_id' => $business->id,
        ])->assertStatus(202);

        $this->assertDatabaseHas('immersive_events', [
            'immersive_plaza_id' => $plaza->id,
            'business_id' => $business->id,
            'type' => ImmersiveEvent::VITRINA_OPENED,
        ]);
    }

    public function test_it_registers_a_product_viewed_event_with_the_resolved_subject(): void
    {
        $plaza = $this->makePlaza();
        $business = $this->publishedBusiness();
        $product = $business->products->first();

        $this->postJson("/api/v1/inmersivo/plazas/{$plaza->id}/eventos", [
            'type' => ImmersiveEvent::PRODUCT_VIEWED,
            'business_id' => $business->id,
            'product_id' => $product->id,
        ])->assertStatus(202);

        $this->assertDatabaseHas('immersive_events', [
            'immersive_plaza_id' => $plaza->id,
            'type' => ImmersiveEvent::PRODUCT_VIEWED,
            'subject_type' => $product->getMorphClass(),
            'subject_id' => $product->id,
        ]);
    }

    public function test_it_stores_small_metadata(): void
    {
        $plaza = $this->makePlaza();

        $this->postJson("/api/v1/inmersivo/plazas/{$plaza->id}/eventos", [
            'type' => ImmersiveEvent::SEARCH_PERFORMED,
            'metadata' => ['query' => 'panaderia', 'categoria' => 'alimentos'],
        ])->assertStatus(202);

        $event = ImmersiveEvent::where('immersive_plaza_id', $plaza->id)->first();

        $this->assertSame(['query' => 'panaderia', 'categoria' => 'alimentos'], $event->metadata);
    }

    public function test_repeated_events_from_the_same_visitor_do_not_duplicate(): void
    {
        $plaza = $this->makePlaza();

        $this->postJson("/api/v1/inmersivo/plazas/{$plaza->id}/eventos", ['type' => ImmersiveEvent::PLAZA_ENTRY])->assertStatus(202);
        $this->postJson("/api/v1/inmersivo/plazas/{$plaza->id}/eventos", ['type' => ImmersiveEvent::PLAZA_ENTRY])->assertStatus(202);
        $this->postJson("/api/v1/inmersivo/plazas/{$plaza->id}/eventos", ['type' => ImmersiveEvent::PLAZA_ENTRY])->assertStatus(202);

        $this->assertSame(1, ImmersiveEvent::where('immersive_plaza_id', $plaza->id)
            ->where('type', ImmersiveEvent::PLAZA_ENTRY)
            ->count());
    }

    public function test_a_known_bot_user_agent_does_not_register_an_event(): void
    {
        $plaza = $this->makePlaza();

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])
            ->postJson("/api/v1/inmersivo/plazas/{$plaza->id}/eventos", ['type' => ImmersiveEvent::PLAZA_ENTRY])
            ->assertStatus(202);

        $this->assertDatabaseCount('immersive_events', 0);
    }

    public function test_an_unknown_event_type_is_rejected(): void
    {
        $plaza = $this->makePlaza();

        $this->postJson("/api/v1/inmersivo/plazas/{$plaza->id}/eventos", [
            'type' => 'algo_inventado',
        ])->assertStatus(422);
    }
}
