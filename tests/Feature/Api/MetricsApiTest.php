<?php

namespace Tests\Feature\Api;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * `GET /api/v1/businesses/{business}/metricas` (5.1/1.8/4.5 del TODO).
 */
class MetricsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_business_team_member_can_see_its_metrics_summary(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Métricas'])->business;
        AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'a']);
        Sanctum::actingAs($owner);

        $this->getJson(route('api.v1.businesses.metricas.show', $business))
            ->assertOk()
            ->assertJsonStructure(['data' => ['summary', 'conversion_funnel', 'product_performance']])
            ->assertJsonPath('data.summary.total_views', 1);
    }

    public function test_someone_outside_the_business_cannot_see_its_metrics(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Ajeno'])->business;

        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.businesses.metricas.show', $business))->assertForbidden();
    }
}
