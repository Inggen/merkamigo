<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Loyalty\Actions\Concerns\AuthorizesLoyaltyEmployees;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use App\Domain\Loyalty\Models\LoyaltyReward;
use App\Domain\Loyalty\Notifications\RedemptionDelivered;
use App\Domain\Loyalty\Support\LoyaltyTokens;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Entregar premio (TODO_Merkapuntos.md, F1.6/F3.4): estado del escáner
 * tras identificar un código de canje. Los puntos ya se descontaron al
 * reservar (`ReserveLoyaltyRedemption`) — entregar solo cierra el estado,
 * nunca vuelve a tocar el saldo.
 */
class DeliverLoyaltyRedemption
{
    use AuthorizesLoyaltyEmployees;

    /**
     * @throws LoyaltyActionException
     */
    public function findByToken(Business $business, string $rawToken): LoyaltyRedemption
    {
        if (! str_starts_with($rawToken, 'rdm_')) {
            throw new LoyaltyActionException('Código de canje inválido.');
        }

        $redemption = LoyaltyRedemption::with('reward')
            ->where('token_hash', LoyaltyTokens::hash($rawToken))
            ->first();

        if (! $redemption || $redemption->reward->business_id !== $business->id) {
            throw new LoyaltyActionException('Código de canje inválido para este negocio.');
        }

        return $redemption;
    }

    /**
     * @throws LoyaltyActionException
     */
    public function handle(Business $business, User $employee, LoyaltyRedemption $redemption): LoyaltyRedemption
    {
        $this->assertAuthorizedEmployee($business, $employee);

        [$redemption, $justDelivered] = DB::transaction(function () use ($business, $employee, $redemption) {
            /** @var LoyaltyRedemption $redemption */
            $redemption = LoyaltyRedemption::whereKey($redemption->id)->lockForUpdate()->firstOrFail();

            if ($redemption->status === LoyaltyRedemption::ENTREGADO) {
                // F1.6, aceptación: "un código consumido no vuelve a
                // entregar" — reintento tras fallo de red ve el mismo
                // resultado, sin error ni doble descuento ni doble aviso.
                return [$redemption, false];
            }

            if ($redemption->status !== LoyaltyRedemption::RESERVADO) {
                throw new LoyaltyActionException('Este código ya no está disponible para entregar (estado: '.$redemption->status.').');
            }

            $reward = LoyaltyReward::whereKey($redemption->reward_id)->lockForUpdate()->firstOrFail();

            if ($reward->business_id !== $business->id) {
                throw new LoyaltyActionException('Este código no pertenece a tu negocio.');
            }

            $redemption->update([
                'status' => LoyaltyRedemption::ENTREGADO,
                'delivered_by_user_id' => $employee->id,
                'delivered_at' => now(),
            ]);

            $reward->decrement('stock_reserved');
            $reward->increment('stock_delivered');
            $reward->decrement('budget_reserved_cents', $redemption->cost_reserved_cents);
            $reward->increment('budget_spent_cents', $redemption->cost_reserved_cents);

            app(RecordAuditLog::class)->handle($employee, 'loyalty.redemption.delivered', $redemption, [
                'business_id' => $business->id,
                'reward_id' => $reward->id,
            ]);

            return [$redemption, true];
        });

        if ($justDelivered) {
            $redemption->account->user->notify(new RedemptionDelivered($redemption));
        }

        return $redemption;
    }
}
