<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;

/**
 * Baja al plan Gratis las suscripciones canceladas cuyo periodo ya pagado
 * terminó, y las que agotaron su periodo de gracia sin renovar (4.1/4.2
 * del TODO — `ProcessSubscriptionRenewals`, que corre justo antes en el
 * scheduler, es quien mete a `en_gracia` e intenta el cobro automático
 * mientras dura la gracia). No hay una degradación inmediata: el negocio
 * disfruta lo que ya pagó hasta el final del periodo o de la gracia.
 */
class ApplyPlanDowngrades
{
    public function handle(): int
    {
        $count = 0;

        $cancelled = Subscription::query()
            ->where('status', Subscription::CANCELADA)
            ->whereNotNull('current_period_ends_at')
            ->where('current_period_ends_at', '<', now())
            ->get();

        $gracePeriodOver = Subscription::query()
            ->where('status', Subscription::EN_GRACIA)
            ->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '<', now())
            ->get();

        $freePlan = Plan::where('slug', 'gratis')->first();

        foreach ($cancelled->merge($gracePeriodOver) as $subscription) {
            if (! $freePlan || $subscription->plan_id === $freePlan->id) {
                continue;
            }

            app(SubscribeToPlan::class)->handle($subscription->business, $freePlan);
            $count++;
        }

        return $count;
    }
}
