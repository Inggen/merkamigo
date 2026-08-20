<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Notifications\SubscriptionRenewalDue;
use App\Domain\Businesses\Models\Business;

/**
 * Corre a diario, antes de `ApplyPlanDowngrades` (4.1/4.2 del TODO):
 *
 * - Suscripciones `activa` cuyo periodo pagado ya terminó: si el negocio
 *   tiene tarjeta guardada (`hasAutoRenewCard()`) se intenta el cobro
 *   automático ahí mismo; si no hay tarjeta o el cobro es rechazado,
 *   entra a `en_gracia` con unos días para regularizar (pagar manual o
 *   arreglar la tarjeta) sin perder el plan de inmediato.
 * - Suscripciones ya `en_gracia` con tarjeta guardada: reintento diario
 *   mientras dure la gracia (dunning simple). Si el negocio no tiene
 *   tarjeta, solo se le recuerda — `ApplyPlanDowngrades` es quien baja a
 *   Gratis una vez pasado `grace_ends_at`.
 *
 * `SubscribeToPlan` nunca actualiza la fila anterior, siempre crea una
 * nueva (para conservar el historial completo — ver su propio docblock),
 * así que una suscripción `activa` que ya se renovó exitosamente sigue
 * existiendo en la BD con su fecha vieja vencida. Consultar `Subscription`
 * directamente volvería a cobrarla cada día para siempre. Por eso todo
 * este action itera sobre `Business` filtrando por su suscripción
 * *vigente* (`latestOfMany()`, la misma que usa `Business::activePlan()`)
 * en vez de sobre filas de `Subscription` sueltas.
 */
class ProcessSubscriptionRenewals
{
    public const GRACE_DAYS = 3;

    public function handle(): int
    {
        $processed = 0;
        $justTransitioned = [];

        $processed += $this->processExpiredActiveSubscriptions($justTransitioned);
        $processed += $this->retryGracePeriodSubscriptions($justTransitioned);

        return $processed;
    }

    /**
     * @param  array<int, int>  $justTransitioned  IDs de negocio recién movidos a `en_gracia` en esta misma
     *                                             corrida — se excluyen del reintento de abajo para no cobrar
     *                                             dos veces el mismo día.
     */
    private function processExpiredActiveSubscriptions(array &$justTransitioned): int
    {
        $count = 0;

        Business::query()
            ->whereHas('subscription', function ($query) {
                $query->where('status', Subscription::ACTIVA)
                    ->whereNotNull('current_period_ends_at')
                    ->where('current_period_ends_at', '<', now())
                    ->whereHas('plan', fn ($plan) => $plan->whereNotNull('price_cents'));
            })
            ->with(['subscription.plan', 'members'])
            ->each(function (Business $business) use (&$count, &$justTransitioned) {
                $subscription = $business->subscription;

                if ($business->hasAutoRenewCard() && $this->attemptCharge($business, $subscription)) {
                    $count++;

                    return;
                }

                $subscription->update([
                    'status' => Subscription::EN_GRACIA,
                    'grace_ends_at' => now()->addDays(self::GRACE_DAYS),
                ]);
                $justTransitioned[] = $business->id;

                if (! $business->hasAutoRenewCard()) {
                    $business->members->each(fn ($member) => $member->notify(new SubscriptionRenewalDue($subscription)));
                }

                $count++;
            });

        return $count;
    }

    /**
     * @param  array<int, int>  $skipBusinessIds
     */
    private function retryGracePeriodSubscriptions(array $skipBusinessIds): int
    {
        $count = 0;

        Business::query()
            ->whereNotIn('id', $skipBusinessIds)
            ->whereHas('subscription', function ($query) {
                $query->where('status', Subscription::EN_GRACIA)
                    ->whereNotNull('grace_ends_at')
                    ->where('grace_ends_at', '>=', now())
                    ->whereHas('plan', fn ($plan) => $plan->whereNotNull('price_cents'));
            })
            ->where('auto_renew_enabled', true)
            ->whereNotNull('wompi_payment_source_id')
            ->with(['subscription.plan'])
            ->each(function (Business $business) use (&$count) {
                if ($this->attemptCharge($business, $business->subscription)) {
                    $count++;
                }
            });

        return $count;
    }

    private function attemptCharge(Business $business, Subscription $subscription): bool
    {
        $payment = app(ChargeSubscriptionRenewal::class)->handle($business, $subscription->plan);

        return $payment->status === Payment::APROBADO;
    }
}
