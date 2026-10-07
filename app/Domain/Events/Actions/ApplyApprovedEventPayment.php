<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Models\EventPaymentAttempt;
use App\Domain\Events\Models\EventReservation;
use App\Domain\Events\Notifications\EventReservationConfirmed;
use App\Domain\Events\Notifications\EventReservationConfirmedForProspect;
use App\Domain\Platform\Actions\RecordAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Aplica el resultado real de un intento de pago Wompi — mismo patrón
 * idempotente que `Marketplace\Actions\ApplyApprovedOrder`, llamado tanto
 * desde el retorno del checkout como desde el webhook del negocio.
 *
 * Fase 4: "pendiente_pago → confirmada solo por pago verificado;
 * pago_fallido o vencida libera la retención. Definir tratamiento de
 * pagos tardíos sin confirmar una franja ya asignada" — un pago aprobado
 * que llega cuando la reserva ya no es `pendiente_pago` (venció o se
 * canceló) se registra como aprobado en el intento, pero NUNCA reasigna
 * ni reconfirma la franja automáticamente: queda para conciliación
 * manual del negocio (aviso + auditoría).
 */
class ApplyApprovedEventPayment
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function handle(EventPaymentAttempt $attempt, string $wompiStatus, ?string $wompiTransactionId, array $rawResponse = []): EventPaymentAttempt
    {
        if (in_array($attempt->status, [EventPaymentAttempt::APROBADO, EventPaymentAttempt::RECHAZADO], true)) {
            return $attempt;
        }

        $status = match ($wompiStatus) {
            'APPROVED' => EventPaymentAttempt::APROBADO,
            'DECLINED', 'ERROR', 'VOIDED' => EventPaymentAttempt::RECHAZADO,
            default => EventPaymentAttempt::PENDIENTE,
        };

        return DB::transaction(function () use ($attempt, $status, $wompiTransactionId, $rawResponse) {
            /** @var EventPaymentAttempt $attempt */
            $attempt = EventPaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if (in_array($attempt->status, [EventPaymentAttempt::APROBADO, EventPaymentAttempt::RECHAZADO], true)) {
                return $attempt;
            }

            $attempt->update([
                'status' => $status,
                'wompi_transaction_id' => $wompiTransactionId,
                'raw_response' => $rawResponse,
            ]);

            if ($status === EventPaymentAttempt::APROBADO) {
                $this->confirmOrReconcile($attempt);
            } elseif ($status === EventPaymentAttempt::RECHAZADO) {
                $reservation = EventReservation::whereKey($attempt->event_reservation_id)->lockForUpdate()->first();

                if ($reservation && $reservation->status === EventReservation::PENDIENTE_PAGO) {
                    $reservation->update(['status' => EventReservation::PAGO_FALLIDO]);
                }
            }

            return $attempt;
        });
    }

    private function confirmOrReconcile(EventPaymentAttempt $attempt): void
    {
        $reservation = EventReservation::whereKey($attempt->event_reservation_id)->lockForUpdate()->first();

        if (! $reservation) {
            return;
        }

        $stillEligible = $reservation->status === EventReservation::PENDIENTE_PAGO
            && (! $reservation->expires_at || $reservation->expires_at->isFuture());

        if (! $stillEligible) {
            // Pago tardío sobre una franja que ya venció/se canceló/se
            // confirmó por otro intento: el dinero SÍ llegó a la cuenta
            // Wompi del negocio, pero la reserva no se reasigna sola.
            Log::warning('event_reservation.late_payment_approved', [
                'reservation_id' => $reservation->id,
                'payment_attempt_id' => $attempt->id,
                'reservation_status' => $reservation->status,
            ]);

            app(RecordAuditLog::class)->handle(null, 'event_reservation.late_payment_approved', $reservation, [
                'payment_attempt_id' => $attempt->id,
                'amount_cents' => $attempt->amount_cents,
            ]);

            return;
        }

        $reservation->update(['status' => EventReservation::CONFIRMADA, 'expires_at' => null]);

        app(RecordAuditLog::class)->handle($reservation->customer, 'event_reservation.confirmed', $reservation, [
            'business_id' => $reservation->business_id,
            'payment_attempt_id' => $attempt->id,
        ]);

        $reservation->business->members->each(fn ($member) => $member->notify(new EventReservationConfirmed($reservation)));

        Notification::route('mail', $reservation->prospect_email)
            ->notify(new EventReservationConfirmedForProspect($reservation));
    }
}
