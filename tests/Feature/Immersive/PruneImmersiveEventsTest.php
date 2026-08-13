<?php

namespace Tests\Feature\Immersive;

use App\Domain\Analytics\Models\ImmersiveEvent;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersivePlaza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IMM-043: retención de datos — poda de `immersive_events` antiguos.
 * Mismo comportamiento que `PruneAnalyticsEventsTest` (hermana de esta
 * suite).
 */
class PruneImmersiveEventsTest extends TestCase
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

        return $experience->plazas()->create([
            'name' => 'Plaza 1',
            'order' => 1,
            'status' => 'activa',
            'spawn_point' => ['x' => 0, 'y' => 0, 'z' => 0, 'rotationY' => 0],
            'navigable_bounds' => ['minX' => -50, 'maxX' => 50, 'minZ' => -50, 'maxZ' => 50],
        ]);
    }

    public function test_the_command_deletes_only_events_older_than_the_retention_window(): void
    {
        $plaza = $this->makePlaza();

        $old = ImmersiveEvent::create(['immersive_plaza_id' => $plaza->id, 'type' => ImmersiveEvent::PLAZA_ENTRY, 'visitor_hash' => 'old']);
        $old->forceFill(['created_at' => now()->subMonths(7)])->save();

        $recent = ImmersiveEvent::create(['immersive_plaza_id' => $plaza->id, 'type' => ImmersiveEvent::PLAZA_ENTRY, 'visitor_hash' => 'recent']);

        $this->artisan('immersive-events:prune')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }

    public function test_the_retention_window_is_configurable_via_option(): void
    {
        $plaza = $this->makePlaza();

        $event = ImmersiveEvent::create(['immersive_plaza_id' => $plaza->id, 'type' => ImmersiveEvent::PLAZA_ENTRY, 'visitor_hash' => 'a']);
        $event->forceFill(['created_at' => now()->subDays(5)])->save();

        $this->artisan('immersive-events:prune', ['--months' => 0])->assertSuccessful();

        $this->assertModelMissing($event);
    }
}
