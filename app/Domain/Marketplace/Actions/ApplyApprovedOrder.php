<?php

namespace App\Domain\Marketplace\Actions;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Marketplace\Notifications\OrderPaid;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Subscriptions\Models\Entitlement;

/**
 * Aplica el resultado real de un pedido según Wompi — mismo patrón que
 * `ApplyApprovedPayment` (llamado tanto desde el retorno del checkout
 * como desde el webhook del negocio, idempotente).
 */
class ApplyApprovedOrder
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function handle(Order $order, string $wompiStatus, ?string $wompiTransactionId, array $rawResponse = []): Order
    {
        if (in_array($order->status, [Order::PAGADO, Order::RECHAZADO], true)) {
            return $order;
        }

        $status = match ($wompiStatus) {
            'APPROVED' => Order::PAGADO,
            'DECLINED', 'ERROR', 'VOIDED' => Order::RECHAZADO,
            default => Order::PENDIENTE,
        };

        $order->update([
            'status' => $status,
            'wompi_transaction_id' => $wompiTransactionId,
            'raw_response' => $rawResponse,
            'paid_at' => $status === Order::PAGADO ? now() : null,
        ]);

        if ($status === Order::PAGADO) {
            app(AccrueCommission::class)->handle($order);
            app(RecordAuditLog::class)->handle($order->buyer, 'order.paid', $order, [
                'business_id' => $order->business_id,
            ]);
            $order->business->members->each(fn ($member) => $member->notify(new OrderPaid($order)));

            if ($order->contentPromotion) {
                AnalyticsEvent::firstOrCreate([
                    'business_id' => $order->business_id,
                    'type' => AnalyticsEvent::PROMOTION_CONVERSION,
                    'subject_type' => $order->contentPromotion->getMorphClass(),
                    'subject_id' => $order->content_promotion_id,
                    'visitor_hash' => hash('sha256', 'order|'.$order->id),
                ]);
            }

            if ($order->liveStream) {
                AnalyticsEvent::firstOrCreate([
                    'business_id' => $order->business_id,
                    'type' => AnalyticsEvent::LIVE_PURCHASE,
                    'subject_type' => $order->liveStream->getMorphClass(),
                    'subject_id' => $order->live_stream_id,
                    'visitor_hash' => hash('sha256', 'live-order|'.$order->id),
                ]);
            }

            // Compra única de un producto digital (Fase 9 del TODO social)
            // — el acceso recurrente vía suscripción se maneja aparte en
            // `Subscriptions\Actions\ChargeSubscriptionPeriod`, que sí
            // sabe hasta cuándo dura el periodo pagado.
            if ($order->customer_subscription_id === null) {
                $products = $order->items()->with('product')->get()->pluck('product')->filter();

                if ($products->isEmpty()) {
                    $products = collect([$order->product]);
                }

                $products->filter->isDigital()->each(function ($product) use ($order): void {
                    Entitlement::firstOrCreate([
                        'user_id' => $order->buyer_user_id,
                        'product_id' => $product->id,
                    ], [
                        'business_id' => $order->business_id,
                        'order_id' => $order->id,
                        'expires_at' => null,
                    ]);
                });
            }
        }

        return $order->fresh();
    }
}
