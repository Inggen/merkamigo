<?php

namespace Tests\Feature\Platform;

use App\Domain\Platform\Models\AuditLog;
use App\Filament\Pages\SystemMaintenance;
use App\Models\User;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Mockery\MockInterface;
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

    public function test_only_selected_migrations_are_executed_oldest_first(): void
    {
        $superadmin = User::factory()->create();
        $this->assignPlatformRole($superadmin, 'superadmin');
        $this->actingAs($superadmin);

        $oldMigration = '2026_10_03_100000_old_change';
        $unselectedMigration = '2026_10_06_100000_unselected_change';
        $newMigration = '2026_10_10_100000_new_change';
        $oldPath = database_path("migrations/{$oldMigration}.php");
        $unselectedPath = database_path("migrations/{$unselectedMigration}.php");
        $newPath = database_path("migrations/{$newMigration}.php");
        $this->mockPendingMigrations([
            $oldMigration => $oldPath,
            $unselectedMigration => $unselectedPath,
            $newMigration => $newPath,
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate', [
                '--force' => true,
                '--path' => [$oldPath, $newPath],
                '--realpath' => true,
            ])
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Migraciones ejecutadas.');

        Livewire::test(SystemMaintenance::class)
            ->assertSet('pendingMigrations', [$newMigration, $unselectedMigration, $oldMigration])
            ->set('selectedMigrations', [$newMigration, $oldMigration])
            ->call('runSelectedMigrations')
            ->assertSet('lastExitCode', 0)
            ->assertSet('lastOutput', 'Migraciones ejecutadas.');

        $this->assertSame('migrate', AuditLog::latest()->first()->metadata['command']);
        $this->assertSame(['--force', '--path', '--realpath'], AuditLog::latest()->first()->metadata['arguments']);
        $this->assertSame([$oldMigration, $newMigration], AuditLog::latest()->first()->metadata['migrations']);
    }

    public function test_a_pending_migration_can_be_discarded_and_restored_without_marking_it_as_ran(): void
    {
        $superadmin = User::factory()->create();
        $this->assignPlatformRole($superadmin, 'superadmin');
        $this->actingAs($superadmin);

        $migration = '2026_10_10_100000_optional_change';
        $this->mockPendingMigrations([
            $migration => database_path("migrations/{$migration}.php"),
        ]);

        Livewire::test(SystemMaintenance::class)
            ->assertSet('pendingMigrations', [$migration])
            ->call('discardMigration', $migration)
            ->assertSet('pendingMigrations', [])
            ->assertSet('discardedMigrations', [$migration])
            ->call('restoreMigration', $migration)
            ->assertSet('pendingMigrations', [$migration])
            ->assertSet('discardedMigrations', []);

        $this->assertDatabaseMissing('migrations', ['migration' => $migration]);
        $this->assertDatabaseMissing('platform_ignored_migrations', ['migration' => $migration]);
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

    /** @param array<string, string> $files */
    private function mockPendingMigrations(array $files): void
    {
        $repository = $this->mock(MigrationRepositoryInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getRan')->andReturn([]);
        });

        $this->mock(Migrator::class, function (MockInterface $mock) use ($files, $repository): void {
            $mock->shouldReceive('getMigrationFiles')->andReturn($files);
            $mock->shouldReceive('getRepository')->andReturn($repository);
        });
    }
}
