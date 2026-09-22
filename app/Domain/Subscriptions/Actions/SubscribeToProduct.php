<?php

namespace App\Domain\Subscriptions\Actions;

use App\Domain\Storefronts\Models\Product;
use App\Domain\Subscriptions\Models\CustomerSubscription;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Confirma la suscripción de un cliente a un producto (Fase 8.2 del TODO
 * social) — se llama una vez la tarjeta ya quedó `AVAILABLE` en Wompi
 * (ver `SaveCustomerPaymentSource`/`FetchCustomerPaymentSourceStatus`).
 * Con periodo de prueba, no cobra todavía; sin prueba, cobra el primer
 * periodo de inmediato con `ChargeSubscriptionPeriod`.
 */
class SubscribeToProduct
{
    public function handle(
        Product $product,
        string $wompiPaymentSourceId,
        string $cardBrand,
        string $cardLastFour,
        User $buyer,
    ): CustomerSubscription {
        $business = $product->business;
        $plan = $product->subscriptionPlan;

        if (! $business->hasWompiConnected() || ! $product->isSubscription() || ! $plan || ! $plan->is_active) {
            throw new InvalidArgumentException('Este producto no está disponible para suscripción en este momento.');
        }

        if (CustomerSubscription::where('product_id', $product->id)
            ->where('buyer_user_id', $buyer->id)
            ->whereIn('status', [CustomerSubscription::PRUEBA, CustomerSubscription::ACTIVA, CustomerSubscription::PAUSADA])
            ->exists()) {
            throw ValidationException::withMessages([
                'product' => 'Ya tienes una suscripción a este producto.',
            ]);
        }

        $isTrial = (int) ($plan->trial_days ?? 0) > 0;

        $subscription = CustomerSubscription::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'subscription_plan_id' => $plan->id,
            'buyer_user_id' => $buyer->id,
            'status' => $isTrial ? CustomerSubscription::PRUEBA : CustomerSubscription::ACTIVA,
            'wompi_payment_source_id' => $wompiPaymentSourceId,
            'card_brand' => $cardBrand,
            'card_last_four' => $cardLastFour,
            'trial_ends_at' => $isTrial ? now()->addDays($plan->trial_days) : null,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => $isTrial ? now()->addDays($plan->trial_days) : null,
        ]);

        if ($isTrial) {
            return $subscription;
        }

        $order = app(ChargeSubscriptionPeriod::class)->handle($subscription);

        if (! $order->isPaid()) {
            throw new InvalidArgumentException('Wompi rechazó el cobro del primer periodo. Intenta con otra tarjeta.');
        }

        return $subscription->fresh();
    }
}
