<?php

namespace App\Domain\Subscriptions\Actions;

use App\Domain\Marketplace\Actions\ApplyApprovedOrder;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Subscriptions\Models\CustomerSubscription;
use App\Domain\Subscriptions\Models\Entitlement;
use App\Support\Wompi\BusinessWompiClient;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cobra UN periodo de una suscripción contra la tarjeta ya guardada del
 * cliente EN LA CUENTA WOMPI DEL NEGOCIO (Fase 8.2 del TODO social) —
 * mismo patrón de sondeo que `Billing\ChargeSubscriptionRenewal` y
 * `Marketplace\ChargeCommission`, ver esos archivos para el porqué del
 * `waitForFinalStatus`. Crea un `Marketplace\Order` más (con
 * `customer_subscription_id`) para reutilizar la comisión de Merkamigo
 * (`AccrueCommission`) tal cual, sin un segundo mecanismo de comisión.
 */
class ChargeSubscriptionPeriod
{
    private const POLL_ATTEMPTS = 6;

    private const POLL_INTERVAL_SECONDS = 2;

    public function handle(CustomerSubscription $subscription): Order
    {
        if (! $subscription->hasSavedCard()) {
            throw new InvalidArgumentException('Esta suscripción no tiene una tarjeta guardada.');
        }

        $business = $subscription->business;
        $product = $subscription->product;
        $plan = $subscription->plan;

        $unitPrice = $product->hasActivePromo() && filled($product->promo_price) ? $product->promo_price : $product->price;
        $amountCents = (int) round((float) $unitPrice * 100);
        $commissionCents = (int) round($amountCents * (float) config('services.marketplace.commission_rate'));

        $order = Order::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'customer_subscription_id' => $subscription->id,
            'buyer_user_id' => $subscription->buyer_user_id,
            'quantity' => 1,
            'unit_price_cents' => $amountCents,
            'amount_cents' => $amountCents,
            'currency' => 'COP',
            'commission_cents' => $commissionCents,
            'reference' => 'MKA-SUB-'.$subscription->id.'-'.Str::upper(Str::random(12)),
            'status' => Order::PENDIENTE,
        ]);

        $client = new BusinessWompiClient($business->wompiCredential);

        $response = $client->chargePaymentSource([
            'amount_in_cents' => $order->amount_cents,
            'currency' => $order->currency,
            'customer_email' => $subscription->buyer->email,
            'reference' => $order->reference,
            'payment_source_id' => (int) $subscription->wompi_payment_source_id,
            'signature' => $client->integritySignature($order->reference, $order->amount_cents, $order->currency),
            'payment_method' => ['installments' => 1],
        ]);

        $transaction = $response['data'] ?? null;

        if (! is_array($transaction)) {
            app(ApplyApprovedOrder::class)->handle($order, 'ERROR', null, is_array($response) ? $response : []);
            $subscription->update(['status' => CustomerSubscription::VENCIDA]);

            return $order->fresh();
        }

        $transaction = $this->waitForFinalStatus($client, $transaction);

        $order = app(ApplyApprovedOrder::class)->handle(
            $order,
            $transaction['status'] ?? 'ERROR',
            $transaction['id'] ?? null,
            $transaction,
        );

        if ($order->isPaid()) {
            $newPeriodEnd = $this->nextPeriodEnd($subscription);

            $subscription->update([
                'status' => CustomerSubscription::ACTIVA,
                'current_period_starts_at' => now(),
                'current_period_ends_at' => $newPeriodEnd,
            ]);

            if ($product->isDigital()) {
                Entitlement::updateOrCreate(
                    ['user_id' => $subscription->buyer_user_id, 'product_id' => $product->id],
                    [
                        'business_id' => $business->id,
                        'order_id' => $order->id,
                        'customer_subscription_id' => $subscription->id,
                        'expires_at' => $newPeriodEnd,
                    ],
                );
            }
        } else {
            // Alcance reducido a propósito (Fase 8.2 del TODO social): sin
            // periodo de gracia ni reintentos todavía — un cobro rechazado
            // vence la suscripción de inmediato, igual de honesto para el
            // negocio que para el cliente mientras no haya volumen real
            // que justifique un dunning más sofisticado como el de
            // `Billing\ProcessSubscriptionRenewals`.
            $subscription->update(['status' => CustomerSubscription::VENCIDA]);
        }

        return $order;
    }

    private function nextPeriodEnd(CustomerSubscription $subscription): \DateTimeInterface
    {
        return now()->add($subscription->plan->periodLength());
    }

    /**
     * @param  array<string, mixed>  $transaction
     * @return array<string, mixed>
     */
    private function waitForFinalStatus(BusinessWompiClient $client, array $transaction): array
    {
        $attempts = 0;

        while (($transaction['status'] ?? null) === 'PENDING' && $attempts < self::POLL_ATTEMPTS && filled($transaction['id'] ?? null)) {
            sleep(self::POLL_INTERVAL_SECONDS);

            $latest = $client->fetchTransaction($transaction['id']);

            if (is_array($latest)) {
                $transaction = $latest;
            }

            $attempts++;
        }

        return $transaction;
    }
}
