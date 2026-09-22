<?php

namespace App\Domain\Subscriptions\Actions;

use App\Domain\Subscriptions\Models\CustomerSubscription;

/**
 * Cancela una suscripción (Fase 8.2 del TODO social) — el acceso sigue
 * hasta el final del periodo ya pagado (`current_period_ends_at`), solo
 * deja de renovarse; `RenewCustomerSubscriptions` la ignora al no estar
 * en `prueba`/`activa`.
 */
class CancelCustomerSubscription
{
    public function handle(CustomerSubscription $subscription): CustomerSubscription
    {
        $subscription->update([
            'status' => CustomerSubscription::CANCELADA,
            'cancelled_at' => now(),
        ]);

        return $subscription->fresh();
    }
}
