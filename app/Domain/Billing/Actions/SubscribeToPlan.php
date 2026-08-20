<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Activa un plan para un negocio (4.1/4.2 del TODO): usada al crear un
 * negocio (plan Gratis) y al aprobarse un pago (plan de pago). Crea una
 * suscripción nueva en vez de reutilizar la anterior, para conservar el
 * historial completo de cambios de plan (auditado, sin tabla aparte).
 */
class SubscribeToPlan
{
    public function handle(Business $business, Plan $plan, ?User $actor = null, ?\DateTimeInterface $periodEndsAt = null): Subscription
    {
        $previousPlan = $business->activePlan();

        // `$periodEndsAt` solo llega desde `ApplyApprovedPayment` — ya se
        // cobró de verdad (a mano o por renovación automática), así que no
        // tiene sentido volver a arrancar un periodo de prueba. Sin
        // `$periodEndsAt` (cambio de plan manual desde admin) sí aplica el
        // trial del plan, como antes.
        $isTrial = $periodEndsAt === null && $plan->trial_days > 0;

        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => $isTrial ? Subscription::PRUEBA : Subscription::ACTIVA,
            'trial_ends_at' => $isTrial ? now()->addDays($plan->trial_days) : null,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => $periodEndsAt,
        ]);

        app(RecordAuditLog::class)->handle($actor, 'subscription.plan_changed', $subscription, [
            'business_id' => $business->id,
            'from_plan' => $previousPlan->slug,
            'to_plan' => $plan->slug,
        ]);

        return $subscription;
    }
}
