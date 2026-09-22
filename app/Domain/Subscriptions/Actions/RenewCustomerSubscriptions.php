<?php

namespace App\Domain\Subscriptions\Actions;

use App\Domain\Subscriptions\Models\CustomerSubscription;
use Illuminate\Support\Facades\Log;

/**
 * Corre a diario (Fase 8.2 del TODO social): cobra el primer periodo real
 * de las suscripciones cuya prueba ya venció, y renueva las que ya
 * cumplieron su periodo pagado. Alcance reducido a propósito — sin
 * periodo de gracia ni reintentos, ver nota en `ChargeSubscriptionPeriod`.
 */
class RenewCustomerSubscriptions
{
    public function handle(): int
    {
        $charged = 0;

        CustomerSubscription::query()
            ->whereIn('status', [CustomerSubscription::PRUEBA, CustomerSubscription::ACTIVA])
            ->where(function ($query) {
                $query->where(fn ($q) => $q->where('status', CustomerSubscription::PRUEBA)->where('trial_ends_at', '<=', now()))
                    ->orWhere(fn ($q) => $q->where('status', CustomerSubscription::ACTIVA)->where('current_period_ends_at', '<=', now()));
            })
            ->with(['business', 'product', 'plan', 'buyer'])
            ->each(function (CustomerSubscription $subscription) use (&$charged) {
                try {
                    app(ChargeSubscriptionPeriod::class)->handle($subscription);
                    $charged++;
                } catch (\InvalidArgumentException $e) {
                    Log::warning("[Subscriptions] Suscripción {$subscription->id} (negocio {$subscription->business_id}) no se pudo renovar: {$e->getMessage()}");
                }
            });

        return $charged;
    }
}
