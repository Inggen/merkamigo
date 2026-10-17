<?php

namespace App\Domain\Marketplace\Actions;

use App\Domain\Billing\Actions\ApplyApprovedPayment;
use App\Domain\Billing\Models\Payment;
use App\Domain\Marketplace\Models\CommissionCharge;
use App\Support\Wompi\WompiClient;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

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
 *
 * PR2 de TODO_VENTAS_RENTABILIDAD.md (hallazgo #4 de la auditoría,
 * docs/auditoria-ventas-rentabilidad.md §2.2, decisión del usuario
 * 2026-10-09 "reintentar automático con backoff"): una comisión
 * `fallida` ya puede volver a intentarse (hasta `MAX_RETRIES` veces,
 * con `RETRY_BACKOFF_DAYS` de espera entre cada una) en vez de quedar
 * abandonada para siempre. También se cierra el otro hueco que
 * encontró esa misma auditoría: si Wompi falla de forma inesperada
 * (timeout, error HTTP) a mitad del cobro, antes la comisión quedaba
 * congelada en `pendiente_cobro` sin que el lote semanal volviera a
 * mirarla nunca — ahora cualquier excepción también cuenta como un
 * intento fallido y programa el siguiente reintento.
 */
class ChargeCommission
{
    private const POLL_ATTEMPTS = 6;

    private const POLL_INTERVAL_SECONDS = 2;

    /**
     * Días de espera antes de cada reintento sucesivo tras una falla.
     * Agotados los tres, la comisión queda en `fallida` con
     * `next_retry_at = null` — ya no se reintenta sola, necesita
     * revisión manual (ver `CommissionCharge::needsManualAttention()`).
     */
    public const RETRY_BACKOFF_DAYS = [2, 5, 10];

    public const MAX_RETRIES = 3;

    public function handle(CommissionCharge $charge): Payment
    {
        if (! in_array($charge->status, [CommissionCharge::ABIERTA, CommissionCharge::FALLIDA], true)) {
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

        try {
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
                $this->scheduleRetry($charge);

                return $payment;
            }

            $transaction = $this->waitForFinalStatus($wompi, $transaction);

            $payment = app(ApplyApprovedPayment::class)->handle(
                $payment,
                $transaction['status'] ?? 'ERROR',
                $transaction['id'] ?? null,
                $transaction,
            );

            if ($payment->status === Payment::APROBADO) {
                $charge->update(['status' => CommissionCharge::PAGADA, 'retry_count' => 0, 'next_retry_at' => null]);
            } else {
                $this->scheduleRetry($charge);
            }

            return $payment;
        } catch (Throwable $e) {
            // Un error inesperado (timeout, HTTP 5xx de Wompi...) no debe
            // dejar la comisión congelada en `pendiente_cobro` para
            // siempre — se trata igual que una falla de cobro normal.
            $this->scheduleRetry($charge);

            throw $e;
        }
    }

    /**
     * Marca la comisión como fallida y, si todavía quedan reintentos,
     * programa el siguiente según `RETRY_BACKOFF_DAYS`. Agotados los
     * reintentos, `next_retry_at` queda en `null` — el lote semanal deja
     * de tocarla y queda visible en el panel de conciliación para que
     * alguien del equipo la gestione a mano.
     */
    private function scheduleRetry(CommissionCharge $charge): void
    {
        $retryCount = $charge->retry_count;

        $nextRetryAt = $retryCount < self::MAX_RETRIES
            ? now()->addDays(self::RETRY_BACKOFF_DAYS[$retryCount])
            : null;

        $charge->update([
            'status' => CommissionCharge::FALLIDA,
            'retry_count' => $retryCount + 1,
            'next_retry_at' => $nextRetryAt,
        ]);
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
