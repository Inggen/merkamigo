<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Loyalty\Actions\Concerns\AuthorizesLoyaltyEmployees;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyEnrollment;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Adhesión voluntaria de un negocio (TODO_Merkapuntos.md, F3.1). Solo el
 * dueño del negocio puede adherirlo.
 */
class EnrollBusinessInLoyalty
{
    use AuthorizesLoyaltyEmployees;

    /**
     * @throws LoyaltyActionException
     */
    public function handle(Business $business, User $actor, string $consentVersion, ?int $budgetCents = null): LoyaltyEnrollment
    {
        $this->assertBusinessOwner($business, $actor, 'Solo el dueño del negocio puede adherirlo a Merkamigo Premia.');

        if ($budgetCents !== null && $budgetCents < 0) {
            throw new LoyaltyActionException('El presupuesto no puede ser negativo.');
        }

        $enrollment = LoyaltyEnrollment::updateOrCreate(
            ['business_id' => $business->id],
            [
                'status' => LoyaltyEnrollment::PENDIENTE,
                'consent_version' => $consentVersion,
                'consented_at' => now(),
                'responsible_user_id' => $actor->id,
                'budget_cents' => $budgetCents,
            ],
        );

        app(RecordAuditLog::class)->handle($actor, 'loyalty.enrollment.saved', $enrollment, [
            'business_id' => $business->id,
        ]);

        return $enrollment;
    }

    /**
     * F3.1: "desactivado hasta configuración válida" — la adhesión solo
     * pasa a `activa` cuando ya existe al menos una política `activa`.
     * Separado de `handle()` porque la política se publica en un paso
     * aparte (`PublishLoyaltyPolicy`), y no siempre en el mismo request.
     */
    public function activate(LoyaltyEnrollment $enrollment, User $actor): LoyaltyEnrollment
    {
        $this->assertBusinessOwner($enrollment->business, $actor, 'Solo el dueño del negocio puede activar el programa.');

        $hasActivePolicy = $enrollment->business->loyaltyPolicies()
            ->where('status', 'activa')
            ->exists();

        if (! $hasActivePolicy) {
            throw new LoyaltyActionException('Falta una política de acumulación activa antes de poder activar el programa.');
        }

        $enrollment->update(['status' => LoyaltyEnrollment::ACTIVA]);

        app(RecordAuditLog::class)->handle($actor, 'loyalty.enrollment.activated', $enrollment, [
            'business_id' => $enrollment->business_id,
        ]);

        return $enrollment->fresh();
    }

    public function withdraw(LoyaltyEnrollment $enrollment, User $actor): LoyaltyEnrollment
    {
        $this->assertBusinessOwner($enrollment->business, $actor, 'Solo el dueño del negocio puede retirarlo del programa.');

        // Reglas de producto: "Al retirar un negocio del programa,
        // conservar historia y resolver canjes pendientes según
        // condiciones acordadas" — retirar NO cancela canjes reservados
        // automáticamente, eso es una decisión operativa aparte.
        $enrollment->update(['status' => LoyaltyEnrollment::RETIRADA]);

        app(RecordAuditLog::class)->handle($actor, 'loyalty.enrollment.withdrawn', $enrollment, [
            'business_id' => $enrollment->business_id,
        ]);

        return $enrollment->fresh();
    }
}
