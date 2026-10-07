<?php

namespace Tests\Feature\Loyalty;

use App\Domain\Loyalty\Models\LoyaltyEnrollment;
use App\Filament\Resources\LoyaltyEnrollments\Pages\ListLoyaltyEnrollments;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Feature\Loyalty\Concerns\SetsUpLoyaltyBusiness;
use Tests\TestCase;

/**
 * TODO_Merkapuntos.md, F4.3 — prueba de extremo a extremo del panel de
 * administración de Merkamigo Premia a través del ciclo de vida real de
 * Livewire que usa Filament (mismo criterio que
 * `FilamentModerationPanelTest`), no solo de la acción de dominio por
 * separado.
 */
class LoyaltyAdminPanelTest extends TestCase
{
    use RefreshDatabase, SetsUpLoyaltyBusiness;

    private function assignPlatformRole(User $user, string $role): void
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');
        $user->assignRole(Role::findOrCreate($role, 'web'));

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');
    }

    public function test_an_admin_can_suspend_an_enrollment_from_the_panel(): void
    {
        [$business] = $this->enrolledBusiness();
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(ListLoyaltyEnrollments::class)
            ->assertOk()
            ->assertSee($business->name)
            ->callTableAction('suspend', $business->loyaltyEnrollment, data: ['reason' => 'Revisión de canjes pendientes.']);

        $this->assertSame(LoyaltyEnrollment::SUSPENDIDA, $business->loyaltyEnrollment->fresh()->status);
    }

    public function test_a_non_admin_cannot_view_the_panel(): void
    {
        [$business] = $this->enrolledBusiness();
        $stranger = User::factory()->create();
        $this->actingAs($stranger);

        Livewire::test(ListLoyaltyEnrollments::class)->assertForbidden();
    }
}
