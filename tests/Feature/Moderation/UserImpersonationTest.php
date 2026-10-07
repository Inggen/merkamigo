<?php

namespace Tests\Feature\Moderation;

use App\Domain\Platform\Actions\StartUserImpersonation;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class UserImpersonationTest extends TestCase
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

    public function test_a_superadmin_can_impersonate_an_entrepreneur_and_return(): void
    {
        $superadmin = User::factory()->create(['experience' => 'cliente']);
        $this->assignPlatformRole($superadmin, 'superadmin');

        $owner = User::factory()->create(['experience' => 'emprendedor']);
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Vitrina soporte'])->business;

        $this->actingAs($superadmin);

        app(StartUserImpersonation::class)->handle($superadmin, $owner);

        $this->assertAuthenticatedAs($owner);
        $this->assertSame($superadmin->id, session(StartUserImpersonation::SESSION_KEY.'.impersonator_id'));

        $this->get(route('emprendedores.negocios.vitrina', $business))
            ->assertOk()
            ->assertSee('Volver a mi cuenta');

        $this->post(route('impersonation.stop'))
            ->assertRedirect(route('filament.admin.resources.users.index'));

        $this->assertAuthenticatedAs($superadmin);
        $this->assertNull(session(StartUserImpersonation::SESSION_KEY));
    }

    /**
     * Paridad entre layouts (2026-10-06): una página de cliente (header
     * normal del sitio) debe seguir mostrando el banner de impersonación
     * igual que una de emprendedor (panel con sidebar) — ver
     * `layouts/cliente.blade.php`.
     */
    public function test_the_impersonation_banner_also_shows_on_a_cliente_experience_page(): void
    {
        $superadmin = User::factory()->create(['experience' => 'cliente']);
        $this->assignPlatformRole($superadmin, 'superadmin');

        $customer = User::factory()->create(['experience' => 'cliente']);

        $this->actingAs($superadmin);

        app(StartUserImpersonation::class)->handle($superadmin, $customer);

        $this->get(route('clientes.actividad'))
            ->assertOk()
            ->assertSee('Volver a mi cuenta');
    }

    public function test_an_admin_cannot_impersonate_users(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');

        $target = User::factory()->create();

        $this->actingAs($admin);

        $this->expectException(AccessDeniedHttpException::class);

        app(StartUserImpersonation::class)->handle($admin, $target);
    }
}
