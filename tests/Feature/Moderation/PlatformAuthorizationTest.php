<?php

namespace Tests\Feature\Moderation;

use App\Filament\Resources\Businesses\BusinessResource;
use App\Filament\Resources\Municipalities\MunicipalityResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 1.9 del TODO: solo roles de plataforma entran al panel de Filament, y un
 * moderador no accede a configuración crítica de superadministración
 * (gestionar roles de usuarios, municipios o categorías).
 */
class PlatformAuthorizationTest extends TestCase
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

    public function test_a_regular_user_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasAnyPlatformRole(['moderator', 'admin', 'superadmin']));
    }

    public function test_a_moderator_can_access_the_admin_panel(): void
    {
        $user = User::factory()->create();
        $this->assignPlatformRole($user, 'moderator');

        $this->assertTrue($user->hasAnyPlatformRole(['moderator', 'admin', 'superadmin']));
    }

    public function test_a_moderator_can_view_but_not_manage_users(): void
    {
        $moderator = User::factory()->create();
        $this->assignPlatformRole($moderator, 'moderator');

        $this->assertTrue(UserResource::canViewAny());

        $this->actingAs($moderator);
        $this->assertFalse(UserResource::canEdit(User::factory()->create()));
    }

    public function test_an_admin_can_manage_users(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');

        $this->actingAs($admin);
        $this->assertTrue(UserResource::canEdit(User::factory()->create()));
    }

    public function test_a_moderator_can_moderate_businesses(): void
    {
        $moderator = User::factory()->create();
        $this->assignPlatformRole($moderator, 'moderator');

        $this->actingAs($moderator);
        $this->assertTrue(BusinessResource::canViewAny());
    }

    public function test_a_moderator_cannot_manage_municipalities(): void
    {
        $moderator = User::factory()->create();
        $this->assignPlatformRole($moderator, 'moderator');

        $this->actingAs($moderator);
        $this->assertFalse(MunicipalityResource::canViewAny());
    }

    public function test_an_admin_can_manage_municipalities(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');

        $this->actingAs($admin);
        $this->assertTrue(MunicipalityResource::canViewAny());
    }
}
