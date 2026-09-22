<?php

namespace App\Domain\Marketplace\Actions;

use App\Domain\Billing\Actions\ApplyApprovedPayment;
use App\Domain\Billing\Models\Payment;
use App\Domain\Marketplace\Models\CommissionCharge;
use App\Support\Wompi\WompiClient;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cobra la comisión acumulada de un negocio contra la tarjeta que YA
 * tiene guardada para la renovación de su suscripción (decisión del
 * usuario: reutilizar ese cobro en vez de construir uno nuevo). Mismo
 * patrón de sondeo que `ChargeSubscriptionRenewal` — ver ese archivo
 * para el porqué del `waitForFinalStatus`.
 *
 * Usa `App\Support\Wompi\WompiClient` (la cuenta de MERKAMIGO) a
 * propósito: esta es la única llamada de todo el flujo de marketplace
 * donde el dinero sí va hacia Merkamigo — es la comisión, no la venta.
 */
class ChargeCommission
{
    private const POLL_ATTEMPTS = 6;

    private const POLL_INTERVAL_SECONDS = 2;

    public function handle(CommissionCharge $charge): Payment
    {
        if ($charge->status !== CommissionCharge::ABIERTA) {
            throw new InvalidArgumentException('Esta comisión ya se cobró o está en proceso.');
        }

        $business = $charge->business;

        if (blank($business->wompi_payment_source_id)) {
            throw new InvalidArgumentException('El negocio no tiene una tarjeta guardada para cobrar la comisión.');
        }

        $charge->update(['status' => CommissionCharge::PENDIENTE_COBRO]);

        $payment = Payment::create([
            'business_id' => $business->id,
            'reference' => 'MKA-COM-'.$business->id.'-'.Str::upper(Str::random(12)),
            'amount_cents' => $charge->commission_cents,
            'currency' => 'COP',
            'status' => Payment::PENDIENTE,
        ]);

        $charge->update(['payment_id' => $payment->id]);

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
            $charge->update(['status' => CommissionCharge::FALLIDA]);

            return $payment;
        }

        $transaction = $this->waitForFinalStatus($wompi, $transaction);

        $payment = app(ApplyApprovedPayment::class)->handle(
            $payment,
            $transaction['status'] ?? 'ERROR',
            $transaction['id'] ?? null,
            $transaction,
        );

        $charge->update([
            'status' => $payment->status === Payment::APROBADO ? CommissionCharge::PAGADA : CommissionCharge::FALLIDA,
        ]);

        return $payment;
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
