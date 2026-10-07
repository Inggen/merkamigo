<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyEnrollment;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Ajustes del administrador de plataforma sobre la adhesión de un negocio
 * (TODO_Merkapuntos.md, F4.3: "Flags por negocio... ajustes tienen
 * permisos/motivo/origen"). Distinta de `EnrollBusinessInLoyalty`: esa es
 * para el dueño del negocio sobre SU propio negocio; esta es para el
 * administrador de la plataforma sobre CUALQUIER negocio, siempre con
 * motivo registrado en la auditoría — "sin borrado de movimientos", por
 * eso suspender nunca toca `loyalty_movements` ni cancela canjes por sí
 * solo.
 */
class AdminReviewLoyaltyEnrollment
{
    /**
     * @throws LoyaltyActionException
     */
    public function suspend(LoyaltyEnrollment $enrollment, User $admin, string $reason): LoyaltyEnrollment
    {
        $this->assertPlatformAdmin($admin);

        if (trim($reason) === '') {
            throw new LoyaltyActionException('Indica un motivo para suspender la adhesión.');
        }

        if ($enrollment->status !== LoyaltyEnrollment::ACTIVA) {
            throw new LoyaltyActionException('Solo se puede suspender una adhesión activa.');
        }

        $enrollment->update(['status' => LoyaltyEnrollment::SUSPENDIDA]);

        app(RecordAuditLog::class)->handle($admin, 'loyalty.enrollment.suspended_by_admin', $enrollment, [
            'business_id' => $enrollment->business_id,
            'reason' => $reason,
        ]);

        return $enrollment->fresh();
    }

    /**
     * @throws LoyaltyActionException
     */
    public function reactivate(LoyaltyEnrollment $enrollment, User $admin, string $reason): LoyaltyEnrollment
    {
        $this->assertPlatformAdmin($admin);

        if (trim($reason) === '') {
            throw new LoyaltyActionException('Indica un motivo para reactivar la adhesión.');
        }

        if ($enrollment->status !== LoyaltyEnrollment::SUSPENDIDA) {
            throw new LoyaltyActionException('Solo se puede reactivar una adhesión suspendida.');
        }

        $enrollment->update(['status' => LoyaltyEnrollment::ACTIVA]);

        app(RecordAuditLog::class)->handle($admin, 'loyalty.enrollment.reactivated_by_admin', $enrollment, [
            'business_id' => $enrollment->business_id,
            'reason' => $reason,
        ]);

        return $enrollment->fresh();
    }

    /**
     * @throws LoyaltyActionException
     */
    private function assertPlatformAdmin(User $admin): void
    {
        if (! $admin->hasAnyPlatformRole(['admin', 'superadmin'])) {
            throw new LoyaltyActionException('Tu cuenta no tiene permiso de administrador de plataforma.');
        }
    }
}
