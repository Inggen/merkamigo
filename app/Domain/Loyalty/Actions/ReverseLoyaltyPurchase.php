<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyMovement;
use App\Domain\Loyalty\Models\LoyaltyPurchase;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Devolución o ajuste sobre una compra ya acreditada (TODO_Merkapuntos.md,
 * F1.7). Puede dejar el saldo de la cuenta en negativo a propósito —
 * "puede crear deuda de puntos y bloquear nuevos canjes, conservando
 * evidencia; nunca truncar saldo a cero silenciosamente" — `ReserveLoyaltyRedemption`
 * ya respeta ese saldo negativo porque siempre suma `loyalty_movements`
 * real, nunca una columna de saldo cacheada.
 */
class ReverseLoyaltyPurchase
{
    /**
     * @throws LoyaltyActionException
     */
    public function handle(LoyaltyPurchase $purchase, int $pointsToReverse, string $idempotencyKey, ?User $actor = null, ?string $reason = null): LoyaltyMovement
    {
        $existing = LoyaltyMovement::where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            return $existing;
        }

        if ($pointsToReverse <= 0) {
            throw new LoyaltyActionException('La cantidad a revertir debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($purchase, $pointsToReverse, $idempotencyKey, $actor, $reason) {
            /** @var LoyaltyPurchase $purchase */
            $purchase = LoyaltyPurchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            // Los movimientos de tipo reversión siempre se crean con
            // puntos negativos (ver más abajo), así que la suma ya viene
            // en negativo — `abs()` la convierte en "cuánto se ha
            // revertido hasta ahora" en positivo.
            $alreadyReversed = abs((int) LoyaltyMovement::where('reference_type', $purchase->getMorphClass())
                ->where('reference_id', $purchase->id)
                ->where('type', LoyaltyMovement::REVERSION)
                ->sum('points'));

            if ($alreadyReversed + $pointsToReverse > $purchase->points_awarded) {
                $remaining = $purchase->points_awarded - $alreadyReversed;

                throw new LoyaltyActionException("Solo quedan {$remaining} puntos de esta compra por revertir.");
            }

            $account = $purchase->business->loyaltyAccounts()
                ->where('user_id', $purchase->customer_user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $movement = LoyaltyMovement::create([
                'account_id' => $account->id,
                'type' => LoyaltyMovement::REVERSION,
                'points' => -$pointsToReverse,
                'reference_type' => $purchase->getMorphClass(),
                'reference_id' => $purchase->id,
                'idempotency_key' => $idempotencyKey,
                'actor_user_id' => $actor?->id,
                'metadata' => $reason ? ['reason' => $reason] : null,
            ]);

            if ($alreadyReversed + $pointsToReverse === $purchase->points_awarded) {
                $purchase->update(['status' => LoyaltyPurchase::REVERTIDA]);
            }

            app(RecordAuditLog::class)->handle($actor, 'loyalty.purchase.reversed', $purchase, [
                'business_id' => $purchase->business_id,
                'points_reversed' => $pointsToReverse,
                'reason' => $reason,
            ]);

            return $movement;
        });
    }
}
