<?php

namespace Tests\Feature\Immersive;

use App\Domain\Analytics\Models\ImmersiveEvent;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Filament\Widgets\ImmersiveEventsOverview;
use App\Filament\Widgets\ImmersivePlazaActivityChart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * IMM-043 (Fase 4 del TODO inmersivo): widgets del dashboard admin —
 * mismo patrón de prueba que `MunicipalityOverviewWidgetTest` (hermana de
 * esta suite).
 */
class ImmersiveAnalyticsWidgetsTest extends TestCase
{
    use RefreshDatabase;

    private function assignPlatformRole(User $user, string $role): void
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');
        $user->assignRole(Role::findOrCreate($role, 'web'));

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');
    }

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

    private function actingAsAdmin(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);
    }

    public function test_the_overview_widget_counts_events_from_the_last_seven_days(): void
    {
        $plaza = $this->makePlaza();

        ImmersiveEvent::create(['immersive_plaza_id' => $plaza->id, 'type' => ImmersiveEvent::PLAZA_ENTRY, 'visitor_hash' => 'a']);
        ImmersiveEvent::create(['immersive_plaza_id' => $plaza->id, 'type' => ImmersiveEvent::PLAZA_ENTRY, 'visitor_hash' => 'b']);
        ImmersiveEvent::create(['immersive_plaza_id' => $plaza->id, 'type' => ImmersiveEvent::VITRINA_OPENED, 'visitor_hash' => 'a']);

        $old = ImmersiveEvent::create(['immersive_plaza_id' => $plaza->id, 'type' => ImmersiveEvent::PLAZA_ENTRY, 'visitor_hash' => 'c']);
        $old->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->actingAsAdmin();

        $component = Livewire::test(ImmersiveEventsOverview::class)->assertSuccessful();

        $stats = (fn () => $this->getStats())->call($component->instance());

        $this->assertSame(2, $stats[0]->getValue());
        $this->assertSame(1, $stats[2]->getValue());
    }

    public function test_the_plaza_activity_chart_renders_successfully(): void
    {
        $plaza = $this->makePlaza();

        ImmersiveEvent::create(['immersive_plaza_id' => $plaza->id, 'type' => ImmersiveEvent::PLAZA_ENTRY, 'visitor_hash' => 'a']);
        ImmersiveEvent::create(['immersive_plaza_id' => $plaza->id, 'type' => ImmersiveEvent::VITRINA_OPENED, 'visitor_hash' => 'a']);

        $this->actingAsAdmin();

        Livewire::test(ImmersivePlazaActivityChart::class)->assertSuccessful();
    }
}
