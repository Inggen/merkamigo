<?php

namespace App\Domain\Marketplace\Actions;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Marketplace\Notifications\GuestOrderPaid;
use App\Domain\Marketplace\Notifications\OrderPaid;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Subscriptions\Models\Entitlement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Aplica el resultado real de un pedido según Wompi — mismo patrón que
 * `ApplyApprovedPayment` (llamado tanto desde el retorno del checkout
 * como desde el webhook del negocio, idempotente).
 *
 * PR2 de TODO_VENTAS_RENTABILIDAD.md (hallazgo #1 de la auditoría,
 * docs/auditoria-ventas-rentabilidad.md §1.2): el retorno del navegador
 * y el webhook pueden llegar casi al mismo tiempo para el MISMO pedido.
 * Antes, cada uno leía `$order->status` de su propia copia en memoria
 * sin bloqueo, así que ambos podían ver "pendiente" y los dos acababan
 * acumulando la comisión. Ahora todo el ciclo leer-decidir-escribir pasa
 * dentro de una transacción con `lockForUpdate()`: el segundo proceso en
 * llegar espera a que el primero termine y ya ve el estado final, así
 * que su propia guarda de idempotencia (abajo) lo detiene antes de
 * repetir nada.
 */
class ApplyApprovedOrder
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function handle(Order $order, string $wompiStatus, ?string $wompiTransactionId, array $rawResponse = []): Order
    {
        [$order, $justPaid, $justRefunded] = DB::transaction(function () use ($order, $wompiStatus, $wompiTransactionId, $rawResponse) {
            /** @var Order $locked */
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            $wasPaid = $locked->status === Order::PAGADO;

            $status = match ($wompiStatus) {
                'APPROVED' => Order::PAGADO,
                'DECLINED', 'ERROR' => Order::RECHAZADO,
                // Un `VOIDED` sobre un pedido que YA estaba pagado es un
                // reembolso real, no un intento que nunca llegó a
                // aprobarse — necesita revertir la comisión ya
                // acumulada (hallazgo #3 de la auditoría, §1.3).
                'VOIDED' => $wasPaid ? Order::REEMBOLSADO : Order::RECHAZADO,
                default => Order::PENDIENTE,
            };

            // Idempotencia: un estado "final" ya aplicado no se repite —
            // salvo la transición pagado→reembolsado, que es la única
            // vez que un estado final da paso a otro.
            $alreadyFinal = in_array($locked->status, [Order::PAGADO, Order::RECHAZADO, Order::REEMBOLSADO], true);
            $isNewRefund = $wasPaid && $status === Order::REEMBOLSADO;

            if ($alreadyFinal && ! $isNewRefund) {
                return [$locked, false, false];
            }

            $locked->update([
                'status' => $status,
                'wompi_transaction_id' => $wompiTransactionId,
                'raw_response' => $rawResponse,
                'paid_at' => $status === Order::PAGADO ? now() : $locked->paid_at,
            ]);

            if ($status === Order::PAGADO) {
                app(AccrueCommission::class)->handle($locked);
            }

            if ($isNewRefund) {
                app(ReverseCommission::class)->handle($locked);
            }

            return [$locked->fresh(), $status === Order::PAGADO, $isNewRefund];
        });

        if ($justPaid) {
            app(RecordAuditLog::class)->handle($order->buyer, 'order.paid', $order, [
                'business_id' => $order->business_id,
            ]);
            $order->business->members->each(fn ($member) => $member->notify(new OrderPaid($order)));

            // PR3 de TODO_VENTAS_RENTABILIDAD.md: un invitado no tiene
            // cuenta donde ver "Mis compras" — sin este correo no se
            // enteraría de que su pago se aprobó ni cómo descargar su
            // producto digital. Mismo patrón que
            // `CreateEventAttendance::notifyConfirmed()`.
            if ($order->isGuestOrder() && filled($order->guest_email)) {
                Notification::route('mail', $order->guest_email)->notify(new GuestOrderPaid($order));
            }

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
            //
            // `Entitlement.user_id` no acepta null — un invitado (PR3)
            // no tiene cuenta a la que atarlo, así que no aplica: su
            // acceso a la descarga es directo contra el pedido pagado
            // vía enlace firmado (`OrderCheckoutController::guestDownload`),
            // no contra un `Entitlement`.
            if ($order->customer_subscription_id === null && ! $order->isGuestOrder()) {
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

        if ($justRefunded) {
            app(RecordAuditLog::class)->handle(null, 'order.refunded', $order, [
                'business_id' => $order->business_id,
            ]);
        }

        return $order;
    }
}
