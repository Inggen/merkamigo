<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\Coupon;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Plan;
use App\Domain\Businesses\Models\Business;
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
    public function handle(Business $business, Plan|BillingProduct $item, ?string $couponCode, User $actor): Payment
    {
        if ($item instanceof Plan && $item->isFree()) {
            throw new InvalidArgumentException('Los planes gratuitos no requieren checkout.');
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
