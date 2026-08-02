<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;

/**
 * Baja al plan Gratis las suscripciones canceladas o vencidas cuyo
 * periodo ya pagado terminó (4.1 del TODO), o marca "en_gracia" a las que
 * apenas vencieron sin cancelar explícitamente (renovación fallida, ver
 * 4.2). No hay una degradación inmediata: el negocio disfruta lo que ya
 * pagó hasta el final del periodo.
 */
class ApplyPlanDowngrades
{
    public function handle(): int
    {
        $count = 0;

        $expired = Subscription::query()
            ->whereIn('status', [Subscription::CANCELADA, Subscription::EN_GRACIA])
            ->whereNotNull('current_period_ends_at')
            ->where('current_period_ends_at', '<', now())
            ->get();

        $freePlan = Plan::where('slug', 'gratis')->first();

        foreach ($expired as $subscription) {
            if (! $freePlan || $subscription->plan_id === $freePlan->id) {
                continue;
            }

            app(SubscribeToPlan::class)->handle($subscription->business, $freePlan);
            $count++;
        }

        return $count;
    }
}
