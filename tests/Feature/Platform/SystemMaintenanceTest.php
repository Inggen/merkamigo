<?php

namespace Tests\Feature\Platform;

use App\Domain\Platform\Models\AuditLog;
use App\Filament\Pages\SystemMaintenance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_superadmin_can_access_system_maintenance(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');

        $this->actingAs($admin);
        Livewire::test(SystemMaintenance::class)->assertForbidden();

        $superadmin = User::factory()->create();
        $this->assignPlatformRole($superadmin, 'superadmin');

        $this->actingAs($superadmin)
            ->get('/admin/mantenimiento')
            ->assertOk()
            ->assertSee('Mantenimiento del sistema')
            ->assertSee('Migraciones de base de datos');
    }

    public function test_a_superadmin_can_run_a_whitelisted_artisan_command(): void
    {
        $superadmin = User::factory()->create();
        $this->assignPlatformRole($superadmin, 'superadmin');
        $this->actingAs($superadmin);

        Artisan::shouldReceive('call')
            ->once()
            ->with('view:cache', [])
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Vistas compiladas correctamente.');

        Livewire::test(SystemMaintenance::class)
            ->call('runCommand', 'view-cache')
            ->assertSet('lastExitCode', 0)
            ->assertSet('lastOutput', 'Vistas compiladas correctamente.');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superadmin->id,
            'action' => 'platform.maintenance_command.executed',
        ]);
        $this->assertSame('view:cache', AuditLog::latest()->first()->metadata['command']);
    }

    public function test_migrations_are_always_executed_with_force(): void
    {
        $superadmin = User::factory()->create();
        $this->assignPlatformRole($superadmin, 'superadmin');
        $this->actingAs($superadmin);

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate', ['--force' => true])
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Migraciones ejecutadas.');

        Livewire::test(SystemMaintenance::class)
            ->call('runMigrations')
            ->assertSet('lastExitCode', 0)
            ->assertSet('lastOutput', 'Migraciones ejecutadas.');

        $this->assertSame('migrate', AuditLog::latest()->first()->metadata['command']);
        $this->assertSame(['--force'], AuditLog::latest()->first()->metadata['arguments']);
    }

    public function test_an_unlisted_command_is_rejected(): void
    {
        $superadmin = User::factory()->create();
        $this->assignPlatformRole($superadmin, 'superadmin');
        $this->actingAs($superadmin);

        Artisan::shouldReceive('call')->never();

        Livewire::test(SystemMaintenance::class)
            ->call('runCommand', 'db-wipe')
            ->assertNotFound();
    }

    private function assignPlatformRole(User $user, string $role): void
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');
        $user->assignRole(Role::findOrCreate($role, 'web'));

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');
    }
}
