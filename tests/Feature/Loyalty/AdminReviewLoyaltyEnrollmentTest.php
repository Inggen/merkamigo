<?php

namespace Tests\Feature\Loyalty;

use App\Domain\Loyalty\Actions\AdminReviewLoyaltyEnrollment;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Feature\Loyalty\Concerns\SetsUpLoyaltyBusiness;
use Tests\TestCase;

/**
 * TODO_Merkapuntos.md, F4.3 — ajustes del administrador de plataforma
 * sobre la adhesión de un negocio, siempre con motivo y auditoría.
 */
class AdminReviewLoyaltyEnrollmentTest extends TestCase
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

    public function test_an_admin_can_suspend_an_active_enrollment_with_a_reason(): void
    {
        [$business] = $this->enrolledBusiness();
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');

        $enrollment = app(AdminReviewLoyaltyEnrollment::class)->suspend(
            $business->loyaltyEnrollment, $admin, 'Reportes de canjes no entregados.',
        );

        $this->assertSame(LoyaltyEnrollment::SUSPENDIDA, $enrollment->status);
    }

    public function test_suspending_requires_a_reason(): void
    {
        [$business] = $this->enrolledBusiness();
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');

        $this->expectException(LoyaltyActionException::class);

        app(AdminReviewLoyaltyEnrollment::class)->suspend($business->loyaltyEnrollment, $admin, '   ');
    }

    public function test_a_non_admin_cannot_suspend_an_enrollment(): void
    {
        [$business, $owner] = $this->enrolledBusiness();

        $this->expectException(LoyaltyActionException::class);

        // Ni siquiera el dueño del propio negocio tiene este permiso —
        // es una acción de administrador de plataforma, no del negocio.
        app(AdminReviewLoyaltyEnrollment::class)->suspend($business->loyaltyEnrollment, $owner, 'Motivo cualquiera.');
    }

    public function test_an_admin_can_reactivate_a_suspended_enrollment(): void
    {
        [$business] = $this->enrolledBusiness();
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'superadmin');

        app(AdminReviewLoyaltyEnrollment::class)->suspend($business->loyaltyEnrollment, $admin, 'Motivo inicial.');

        $enrollment = app(AdminReviewLoyaltyEnrollment::class)->reactivate(
            $business->loyaltyEnrollment->fresh(), $admin, 'Resuelto tras revisión.',
        );

        $this->assertSame(LoyaltyEnrollment::ACTIVA, $enrollment->status);
    }

    public function test_cannot_suspend_an_enrollment_that_is_not_active(): void
    {
        [$business] = $this->enrolledBusiness();
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');

        app(AdminReviewLoyaltyEnrollment::class)->suspend($business->loyaltyEnrollment, $admin, 'Primera suspensión.');

        $this->expectException(LoyaltyActionException::class);

        app(AdminReviewLoyaltyEnrollment::class)->suspend($business->loyaltyEnrollment->fresh(), $admin, 'Segunda suspensión.');
    }
}
