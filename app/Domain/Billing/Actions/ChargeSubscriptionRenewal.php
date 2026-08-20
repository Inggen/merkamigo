<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Plan;
use App\Domain\Businesses\Models\Business;
use App\Support\Wompi\WompiClient;
use Illuminate\Support\Str;

/**
 * Cobra automáticamente la renovación mensual de un negocio contra su
 * fuente de pago guardada (4.2 del TODO) — sin el cliente presente, a
 * diferencia del checkout normal que redirige a Wompi. Usa el mismo
 * `Payment` y el mismo `ApplyApprovedPayment` que el checkout manual, así
 * que el webhook de Wompi que llega después es idempotente con el efecto
 * ya aplicado aquí de forma síncrona cuando Wompi responde al momento.
 *
 * Probado contra el sandbox real de Wompi: la respuesta inmediata de
 * `POST /transactions` casi nunca trae el estado final — normalmente
 * queda `PENDING` unos segundos mientras se resuelve, y solo después pasa
 * a `APPROVED`/`DECLINED`. Como este cobro corre en un job programado (sin
 * nadie esperando en el navegador), conviene esperar un poco aquí mismo en
 * vez de asumir que "no aprobado al instante" significa que falló — de lo
 * contrario `ProcessSubscriptionRenewals` metería a periodo de gracia
 * cobros que en realidad sí se iban a aprobar segundos después.
 */
class ChargeSubscriptionRenewal
{
    private const POLL_ATTEMPTS = 6;

    private const POLL_INTERVAL_SECONDS = 2;

    public function handle(Business $business, Plan $plan): Payment
    {
        $payment = Payment::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'reference' => 'MKA-RENOV-'.$business->id.'-'.Str::upper(Str::random(12)),
            'amount_cents' => $plan->price_cents,
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
