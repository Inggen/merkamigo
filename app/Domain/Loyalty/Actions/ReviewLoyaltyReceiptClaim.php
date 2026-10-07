<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Actions\Concerns\AuthorizesLoyaltyEmployees;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyPurchase;
use App\Domain\Loyalty\Models\LoyaltyReceiptClaim;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Revisión de una solicitud excepcional con recibo (TODO_Merkapuntos.md,
 * F2.7/F3.6). "No hay acreditación automática por imagen" — un empleado
 * mira el recibo y decide: aprobarlo crea una acreditación única (misma
 * acción `RegisterLoyaltyPurchase` que usa el escáner, con el mismo
 * resguardo de referencia/idempotencia), o rechazarlo con motivo.
 */
class ReviewLoyaltyReceiptClaim
{
    use AuthorizesLoyaltyEmployees;

    /**
     * @throws LoyaltyActionException
     */
    public function approve(LoyaltyReceiptClaim $claim, User $actor, int $eligibleAmountCents, string $idempotencyKey): LoyaltyPurchase
    {
        $this->assertAuthorizedEmployee($claim->business, $actor);

        if ($claim->status !== LoyaltyReceiptClaim::PENDIENTE) {
            throw new LoyaltyActionException('Esta solicitud ya fue revisada.');
        }

        $purchase = app(RegisterLoyaltyPurchase::class)->handle(
            $claim->business, $actor, $claim->customer, $eligibleAmountCents, $idempotencyKey,
            externalReference: 'claim-'.$claim->id,
        );

        $claim->update([
            'status' => LoyaltyReceiptClaim::APROBADA,
            'linked_purchase_id' => $purchase->id,
            'reviewed_by_user_id' => $actor->id,
            'reviewed_at' => now(),
        ]);

        app(RecordAuditLog::class)->handle($actor, 'loyalty.receipt_claim.approved', $claim, [
            'business_id' => $claim->business_id,
            'purchase_id' => $purchase->id,
        ]);

        return $purchase;
    }

    /**
     * @throws LoyaltyActionException
     */
    public function reject(LoyaltyReceiptClaim $claim, User $actor, string $reason): LoyaltyReceiptClaim
    {
        $this->assertAuthorizedEmployee($claim->business, $actor);

        if ($claim->status !== LoyaltyReceiptClaim::PENDIENTE) {
            throw new LoyaltyActionException('Esta solicitud ya fue revisada.');
        }

        $claim->update([
            'status' => LoyaltyReceiptClaim::RECHAZADA,
            'reviewed_by_user_id' => $actor->id,
            'reviewed_at' => now(),
            'decision_reason' => $reason,
        ]);

        app(RecordAuditLog::class)->handle($actor, 'loyalty.receipt_claim.rejected', $claim, [
            'business_id' => $claim->business_id,
            'reason' => $reason,
        ]);

        return $claim;
    }
}
