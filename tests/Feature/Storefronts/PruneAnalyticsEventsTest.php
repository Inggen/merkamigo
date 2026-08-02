<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 0.6 del TODO: retención de datos — poda de `analytics_events` antiguos.
 */
class PruneAnalyticsEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_command_deletes_only_events_older_than_the_retention_window(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Retención'])->business;

        $old = AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'old']);
        $old->forceFill(['created_at' => now()->subMonths(13)])->save();

        $recent = AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'recent']);

        $this->artisan('analytics:prune-events')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }

    public function test_the_retention_window_is_configurable_via_option(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Retención Corta'])->business;

        $event = AnalyticsEvent::create(['business_id' => $business->id, 'type' => AnalyticsEvent::VITRINA_VIEW, 'visitor_hash' => 'a']);
        $event->forceFill(['created_at' => now()->subDays(5)])->save();

        $this->artisan('analytics:prune-events', ['--months' => 0])->assertSuccessful();

        $this->assertModelMissing($event);
    }
}
