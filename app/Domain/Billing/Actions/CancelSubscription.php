<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Cancela una suscripción (4.1 del TODO). No degrada al plan Gratis de
 * inmediato — el negocio conserva su plan hasta el fin del periodo ya
 * pagado (`current_period_ends_at`); `billing:apply-plan-downgrades`
 * (comando diario) hace la baja real una vez pasada esa fecha.
 */
class CancelSubscription
{
    public function handle(Subscription $subscription, User $actor): Subscription
    {
        $subscription->update([
            'status' => Subscription::CANCELADA,
            'cancelled_at' => now(),
        ]);

        app(RecordAuditLog::class)->handle($actor, 'subscription.cancelled', $subscription, [
            'business_id' => $subscription->business_id,
        ]);

        return $subscription->fresh();
    }
}
