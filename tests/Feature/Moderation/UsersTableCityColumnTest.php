<?php

namespace Tests\Feature\Moderation;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pedido del usuario: columna "Ciudad" (municipio de la vitrina) y su
 * filtro en la tabla de Usuarios del admin.
 */
class UsersTableCityColumnTest extends TestCase
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

    public function test_the_city_column_shows_the_municipality_of_the_users_business(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');

        $cajica = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $owner = User::factory()->create(['name' => 'Dueño Cajicá']);
        app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Cajicá', 'municipality_id' => $cajica->id]);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertSee('Dueño Cajicá')
            ->assertSee('Cajicá');
    }

    public function test_the_city_filter_narrows_users_to_the_selected_municipality(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');

        $cajica = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $zipaquira = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);

        $ownerCajica = User::factory()->create(['name' => 'Dueño Cajicá']);
        app(CreateStorefront::class)->handle($ownerCajica, ['name' => 'Negocio Cajicá', 'municipality_id' => $cajica->id]);

        $ownerZipa = User::factory()->create(['name' => 'Dueño Zipaquirá']);
        app(CreateStorefront::class)->handle($ownerZipa, ['name' => 'Negocio Zipaquirá', 'municipality_id' => $zipaquira->id]);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertSee('Dueño Cajicá')
            ->assertSee('Dueño Zipaquirá')
            ->filterTable('municipality', $cajica->id)
            ->assertSee('Dueño Cajicá')
            ->assertDontSee('Dueño Zipaquirá');
    }
}
