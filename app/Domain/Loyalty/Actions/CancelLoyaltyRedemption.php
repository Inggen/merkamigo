<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyMovement;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use App\Domain\Loyalty\Models\LoyaltyReward;
use App\Domain\Loyalty\Notifications\RedemptionPointsReleased;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cancelar o expirar un canje reservado (TODO_Merkapuntos.md, F1.6): libera
 * puntos, stock y presupuesto reservado. Usada tanto por el cliente
 * (cancelación voluntaria de su propio canje activo) como por el comando
 * programado de vencimiento (`loyalty:expire-redemptions`).
 *
 * Reglas de producto: "Si ya fue entregado, cancelarlo desde el cliente no
 * está permitido" — ese chequeo de "desde el cliente" vive en el llamador
 * (compara `$actor` con el dueño de la cuenta); esta acción en sí misma
 * solo garantiza que nunca se cancela algo que ya se entregó.
 */
class CancelLoyaltyRedemption
{
    public const RAZON_CLIENTE = 'cancelado_por_cliente';

    public const RAZON_VENCIDO = 'vencido';

    public const RAZON_NEGOCIO = 'cancelado_por_negocio';

    /**
     * @throws LoyaltyActionException
     */
    public function handle(LoyaltyRedemption $redemption, string $reason, ?User $actor = null): LoyaltyRedemption
    {
        [$redemption, $justReleased] = DB::transaction(function () use ($redemption, $reason, $actor) {
            /** @var LoyaltyRedemption $redemption */
            $redemption = LoyaltyRedemption::whereKey($redemption->id)->lockForUpdate()->firstOrFail();

            if (in_array($redemption->status, [LoyaltyRedemption::CANCELADO, LoyaltyRedemption::EXPIRADO], true)) {
                // Idempotente: el comando de vencimiento puede reprocesar
                // el mismo registro sin duplicar la liberación.
                return [$redemption, false];
            }

            if ($redemption->status === LoyaltyRedemption::ENTREGADO) {
                throw new LoyaltyActionException('Este canje ya fue entregado y no se puede cancelar.');
            }

            $reward = LoyaltyReward::whereKey($redemption->reward_id)->lockForUpdate()->firstOrFail();

            $newStatus = $reason === self::RAZON_VENCIDO ? LoyaltyRedemption::EXPIRADO : LoyaltyRedemption::CANCELADO;

            $redemption->update([
                'status' => $newStatus,
                'cancelled_reason' => $reason,
            ]);

            LoyaltyMovement::create([
                'account_id' => $redemption->account_id,
                'type' => LoyaltyMovement::LIBERACION_CANJE,
                'points' => $redemption->points_reserved,
                'reference_type' => $redemption->getMorphClass(),
                'reference_id' => $redemption->id,
                'idempotency_key' => "movement_release_{$redemption->idempotency_key}",
                'metadata' => ['reason' => $reason],
            ]);

            $reward->decrement('stock_reserved');
            $reward->decrement('budget_reserved_cents', $redemption->cost_reserved_cents);

            app(RecordAuditLog::class)->handle($actor, 'loyalty.redemption.'.$newStatus, $redemption, [
                'reward_id' => $reward->id,
                'reason' => $reason,
            ]);

            return [$redemption, true];
        });

        if ($justReleased) {
            $redemption->account->user->notify(new RedemptionPointsReleased($redemption));
        }

        return $redemption;
    }
}
