<?php

namespace Tests\Feature\Discovery;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Discovery\Models\RecentlyViewedBusiness;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 1.1.1 del TODO: historial básico de negocios vistos, solo con
 * consentimiento explícito del Cliente.
 */
class RecentlyViewedBusinessesTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBusiness(): Business
    {
        $suffix = uniqid();
        $municipality = Municipality::firstOrCreate(['slug' => 'cajica'], ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::firstOrCreate(['slug' => 'alimentos'], ['name' => 'Alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Panadería Historial '.$suffix,
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Panes frescos.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Pan', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        app(PublishStorefront::class)->handle($business, $owner);

        return $business->fresh();
    }

    public function test_visiting_a_vitrina_is_not_remembered_without_consent(): void
    {
        $business = $this->publishedBusiness();
        $visitor = User::factory()->create(['remember_recently_viewed' => false]);

        $this->actingAs($visitor)->get(route('vitrinas.show', $business))->assertOk();

        $this->assertDatabaseCount('recently_viewed_businesses', 0);
    }

    public function test_visiting_a_vitrina_is_remembered_with_consent(): void
    {
        $business = $this->publishedBusiness();
        $visitor = User::factory()->create(['remember_recently_viewed' => true]);

        $this->actingAs($visitor)->get(route('vitrinas.show', $business))->assertOk();

        $this->assertDatabaseHas('recently_viewed_businesses', [
            'user_id' => $visitor->id,
            'business_id' => $business->id,
        ]);
    }

    public function test_a_guest_visit_is_never_recorded(): void
    {
        $business = $this->publishedBusiness();

        $this->get(route('vitrinas.show', $business))->assertOk();

        $this->assertDatabaseCount('recently_viewed_businesses', 0);
    }

    public function test_only_the_most_recent_visits_are_kept(): void
    {
        $visitor = User::factory()->create(['remember_recently_viewed' => true]);

        for ($i = 0; $i < 22; $i++) {
            $business = $this->publishedBusiness();
            $this->actingAs($visitor)->get(route('vitrinas.show', $business));
        }

        $this->assertSame(20, RecentlyViewedBusiness::where('user_id', $visitor->id)->count());
    }
}
