<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Billing\Models\Coupon;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Plan;
use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Models\ContentPromotion;
use App\Models\User;
use App\Support\Wompi\WompiClient;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Crea la constancia de pago y calcula la firma de integridad en el
 * servidor (4.2 del TODO) — nunca en el navegador, para no exponer el
 * secreto de integridad.
 */
class CreatePaymentCheckout
{
    public function handle(Business $business, Plan|BillingProduct $item, ?string $couponCode, User $actor, ?ContentPromotion $promotion = null): Payment
    {
        if ($item instanceof Plan && $item->isFree()) {
            throw new InvalidArgumentException('Los planes gratuitos no requieren checkout.');
        }

        // PR4 de TODO_VENTAS_RENTABILIDAD.md (decisión #5 del usuario
        // 2026-10-09): nunca cobrar dos veces una capacidad que el
        // negocio ya tiene activa, ya sea por su plan o por una compra
        // anterior — la vista ya la oculta, esto es la defensa real
        // (cualquier vía de entrada futura que llegue hasta acá queda
        // cubierta igual).
        if ($item instanceof BillingProduct && $item->kind === BillingProduct::ENTITLEMENT) {
            $key = $item->payload['entitlement_key'] ?? null;

            if ($key === BusinessEntitlement::AI_CHATBOT && $business->canUseAiChatbot()) {
                throw new InvalidArgumentException('Tu negocio ya tiene acceso al asistente IA — por tu plan o por una suscripción anterior.');
            }

            if ($key && $business->hasEntitlement($key)) {
                throw new InvalidArgumentException('Tu negocio ya tiene esta capacidad activa.');
            }

            // Decisión #4: un add-on recurrente (`expires_in_days` no
            // nulo) solo se puede activar si ya hay una tarjeta
            // guardada para cobrar la renovación — de lo contrario
            // nadie podría pagar el segundo mes.
            if (filled($item->payload['expires_in_days'] ?? null) && ! $business->hasAutoRenewCard()) {
                throw new InvalidArgumentException('Guarda una tarjeta para la renovación automática antes de activar este add-on.');
            }
        }

        $amountCents = $item instanceof Plan ? $item->price_cents : $item->price_cents;

        $coupon = null;

        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();

            if (! $coupon || ! $coupon->isRedeemable()) {
                throw new InvalidArgumentException('Ese cupón no es válido o ya venció.');
            }

            $amountCents = $coupon->discountedAmountCents($amountCents);
        }

        $payment = Payment::create([
            'business_id' => $business->id,
            'plan_id' => $item instanceof Plan ? $item->id : null,
            'billing_product_id' => $item instanceof BillingProduct ? $item->id : null,
            'content_promotion_id' => $promotion?->id,
            'reference' => 'MKA-'.$business->id.'-'.Str::upper(Str::random(12)),
            'amount_cents' => $amountCents,
            'currency' => 'COP',
            'status' => Payment::PENDIENTE,
            'coupon_code' => $coupon?->code,
        ]);

        if ($coupon) {
            $coupon->increment('redeemed_count');
        }

        return $payment;
    }

    public function integritySignature(Payment $payment): string
    {
        return app(WompiClient::class)->integritySignature($payment->reference, $payment->amount_cents, $payment->currency);
    }
}
