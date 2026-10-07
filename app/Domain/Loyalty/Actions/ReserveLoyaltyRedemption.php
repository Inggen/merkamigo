<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyAccount;
use App\Domain\Loyalty\Models\LoyaltyMovement;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use App\Domain\Loyalty\Models\LoyaltyReward;
use App\Domain\Loyalty\Support\LoyaltyTokens;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Reserva atómica de un canje (TODO_Merkapuntos.md, F1.5/F2.5): "Usar N
 * Merkapuntos" hace la reserva y abre el código en la misma sección, sin
 * pantalla adicional de confirmación.
 *
 * Protocolo de bloqueo: toda acción que lee o cambia el saldo/stock/
 * presupuesto de un premio o una cuenta hace `lockForUpdate()` sobre esas
 * filas ANTES de leer el saldo derivado de `loyalty_movements` — así dos
 * reservas simultáneas de la última unidad, o del último presupuesto,
 * nunca pasan ambas.
 */
class ReserveLoyaltyRedemption
{
    /**
     * `token` es el valor crudo para generar el código — se descifra de
     * `token_encrypted` (cast `encrypted`), nunca se recalcula ni se
     * guarda en claro. F2.5, aceptación: "reabrir muestra el mismo canje
     * activo correspondiente" — por eso una repetición idempotente
     * también devuelve el mismo token, no uno distinto.
     *
     * @return array{redemption: LoyaltyRedemption, token: string}
     *
     * @throws LoyaltyActionException
     */
    public function handle(User $customer, LoyaltyReward $reward, string $idempotencyKey): array
    {
        $existing = LoyaltyRedemption::where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            return ['redemption' => $existing, 'token' => $existing->token_encrypted];
        }

        return DB::transaction(function () use ($customer, $reward, $idempotencyKey) {
            /** @var LoyaltyReward $reward */
            $reward = LoyaltyReward::whereKey($reward->id)->lockForUpdate()->firstOrFail();

            if (! $reward->isRedeemable()) {
                throw new LoyaltyActionException($this->reasonUnavailable($reward));
            }

            $account = LoyaltyAccount::firstOrCreate(
                ['business_id' => $reward->business_id, 'user_id' => $customer->id],
                ['status' => LoyaltyAccount::ACTIVA],
            );
            $account = LoyaltyAccount::whereKey($account->id)->lockForUpdate()->first();

            if ($account->status !== LoyaltyAccount::ACTIVA) {
                throw new LoyaltyActionException('Tu cuenta de Merkapuntos en este negocio no está activa.');
            }

            $available = (int) $account->movements()->sum('points');

            if ($available < $reward->points_cost) {
                throw new LoyaltyActionException("No tienes suficientes Merkapuntos todavía. Te faltan {$this->missing($available, $reward->points_cost)} puntos.");
            }

            $ttlMinutes = (int) config('loyalty.redemption_token_ttl_minutes', 15);
            ['token' => $token, 'hash' => $hash] = LoyaltyTokens::generate('rdm');

            $redemption = LoyaltyRedemption::create([
                'account_id' => $account->id,
                'reward_id' => $reward->id,
                'reward_snapshot' => [
                    'title' => $reward->title,
                    'type' => $reward->type,
                    'points_cost' => $reward->points_cost,
                    'full_cost_cents' => $reward->full_cost_cents,
                    'terms' => $reward->terms,
                ],
                'points_reserved' => $reward->points_cost,
                'cost_reserved_cents' => $reward->full_cost_cents,
                'token_hash' => $hash,
                'token_encrypted' => $token,
                'status' => LoyaltyRedemption::RESERVADO,
                'expires_at' => now()->addMinutes($ttlMinutes),
                'idempotency_key' => $idempotencyKey,
            ]);

            LoyaltyMovement::create([
                'account_id' => $account->id,
                'type' => LoyaltyMovement::RESERVA_CANJE,
                'points' => -$reward->points_cost,
                'reference_type' => $redemption->getMorphClass(),
                'reference_id' => $redemption->id,
                'idempotency_key' => "movement_{$idempotencyKey}",
                'actor_user_id' => $customer->id,
            ]);

            $reward->increment('stock_reserved');
            $reward->increment('budget_reserved_cents', $reward->full_cost_cents);

            app(RecordAuditLog::class)->handle($customer, 'loyalty.redemption.reserved', $redemption, [
                'business_id' => $reward->business_id,
                'reward_id' => $reward->id,
                'points_reserved' => $reward->points_cost,
            ]);

            return ['redemption' => $redemption, 'token' => $token];
        });
    }

    private function missing(int $available, int $needed): int
    {
        return max(0, $needed - $available);
    }

    private function reasonUnavailable(LoyaltyReward $reward): string
    {
        if ($reward->status !== LoyaltyReward::PUBLICADO) {
            return 'Este premio no está disponible en este momento.';
        }

        if (! $reward->isWithinValidity()) {
            return 'Este premio ya no está vigente.';
        }

        if ($reward->stockAvailable() === 0) {
            return 'Este premio está agotado.';
        }

        return 'Este premio no está disponible en este momento.';
    }
}
