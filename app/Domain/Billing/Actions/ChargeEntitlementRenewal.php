<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\Payment;
use App\Domain\Businesses\Models\Business;
use App\Support\Wompi\WompiClient;
use Illuminate\Support\Str;

/**
 * Cobra automáticamente la renovación mensual de un add-on tipo
 * `BillingProduct::ENTITLEMENT` recurrente (PR4 de
 * TODO_VENTAS_RENTABILIDAD.md, decisión #4 del usuario 2026-10-09: el
 * asistente IA pasa de pago único "de por vida" a suscripción mensual
 * con cobro automático) — mismo patrón exacto que
 * `ChargeSubscriptionRenewal` (misma tarjeta guardada del negocio,
 * mismo sondeo de estado final), pero contra `billing_product_id` en
 * vez de `plan_id`. Reutiliza el mismo `ApplyApprovedPayment` →
 * `ApplyBillingProductPurchase::applyEntitlement()` que ya sabe
 * extender `expires_at` del `BusinessEntitlement` existente en vez de
 * duplicarlo — nada de esa lógica de efecto se duplica aquí.
 */
class ChargeEntitlementRenewal
{
    private const POLL_ATTEMPTS = 6;

    private const POLL_INTERVAL_SECONDS = 2;

    public function handle(Business $business, BillingProduct $billingProduct): Payment
    {
        $payment = Payment::create([
            'business_id' => $business->id,
            'billing_product_id' => $billingProduct->id,
            'reference' => 'MKA-RENOV-ENT-'.$business->id.'-'.Str::upper(Str::random(12)),
            'amount_cents' => $billingProduct->price_cents,
            'currency' => 'COP',
            'status' => Payment::PENDIENTE,
        ]);

        $wompi = app(WompiClient::class);

        $response = $wompi->chargePaymentSource([
            'amount_in_cents' => $payment->amount_cents,
            'currency' => $payment->currency,
            'customer_email' => $business->members->first()?->email ?? config('mail.from.address'),
            'reference' => $payment->reference,
            'payment_source_id' => (int) $business->wompi_payment_source_id,
            'signature' => $wompi->integritySignature($payment->reference, $payment->amount_cents, $payment->currency),
            'payment_method' => ['installments' => 1],
        ]);

        $transaction = $response['data'] ?? null;

        if (! is_array($transaction)) {
            return $payment;
        }

        $transaction = $this->waitForFinalStatus($wompi, $transaction);

        return app(ApplyApprovedPayment::class)->handle(
            $payment,
            $transaction['status'] ?? 'ERROR',
            $transaction['id'] ?? null,
            $transaction,
        );
    }

    /**
     * @param  array<string, mixed>  $transaction
     * @return array<string, mixed>
     */
    private function waitForFinalStatus(WompiClient $wompi, array $transaction): array
    {
        $attempts = 0;

        while (($transaction['status'] ?? null) === 'PENDING' && $attempts < self::POLL_ATTEMPTS && filled($transaction['id'] ?? null)) {
            sleep(self::POLL_INTERVAL_SECONDS);

            $latest = $wompi->fetchTransaction($transaction['id']);

            if (is_array($latest)) {
                $transaction = $latest;
            }

            $attempts++;
        }

        return $transaction;
    }
}
