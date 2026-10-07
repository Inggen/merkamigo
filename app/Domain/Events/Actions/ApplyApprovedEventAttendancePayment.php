<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Models\EventAttendance;
use App\Domain\Events\Models\EventAttendancePaymentAttempt;
use App\Domain\Platform\Actions\RecordAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Aplica el resultado real de un intento de pago Wompi de una reserva de
 * asistencia — mismo patrón idempotente que `ApplyApprovedEventPayment`
 * (reservas de espacio): un pago aprobado que llega cuando la reserva ya
 * no es `pendiente_pago` (venció o se canceló) se registra como
 * aprobado en el intento, pero nunca reasigna el cupo solo.
 */
class ApplyApprovedEventAttendancePayment
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function handle(EventAttendancePaymentAttempt $attempt, string $wompiStatus, ?string $wompiTransactionId, array $rawResponse = []): EventAttendancePaymentAttempt
    {
        if (in_array($attempt->status, [EventAttendancePaymentAttempt::APROBADO, EventAttendancePaymentAttempt::RECHAZADO], true)) {
            return $attempt;
        }

        $status = match ($wompiStatus) {
            'APPROVED' => EventAttendancePaymentAttempt::APROBADO,
            'DECLINED', 'ERROR', 'VOIDED' => EventAttendancePaymentAttempt::RECHAZADO,
            default => EventAttendancePaymentAttempt::PENDIENTE,
        };

        return DB::transaction(function () use ($attempt, $status, $wompiTransactionId, $rawResponse) {
            /** @var EventAttendancePaymentAttempt $attempt */
            $attempt = EventAttendancePaymentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if (in_array($attempt->status, [EventAttendancePaymentAttempt::APROBADO, EventAttendancePaymentAttempt::RECHAZADO], true)) {
                return $attempt;
            }

            $attempt->update([
                'status' => $status,
                'wompi_transaction_id' => $wompiTransactionId,
                'raw_response' => $rawResponse,
            ]);

            if ($status === EventAttendancePaymentAttempt::APROBADO) {
                $this->confirmOrReconcile($attempt);
            } elseif ($status === EventAttendancePaymentAttempt::RECHAZADO) {
                $attendance = EventAttendance::whereKey($attempt->event_attendance_id)->lockForUpdate()->first();

                if ($attendance && $attendance->status === EventAttendance::PENDIENTE_PAGO) {
                    $attendance->update(['status' => EventAttendance::PAGO_FALLIDO]);
                }
            }

            return $attempt;
        });
    }

    private function confirmOrReconcile(EventAttendancePaymentAttempt $attempt): void
    {
        $attendance = EventAttendance::whereKey($attempt->event_attendance_id)->lockForUpdate()->first();

        if (! $attendance) {
            return;
        }

        $stillEligible = $attendance->status === EventAttendance::PENDIENTE_PAGO
            && (! $attendance->expires_at || $attendance->expires_at->isFuture());

        if (! $stillEligible) {
            Log::warning('event_attendance.late_payment_approved', [
                'attendance_id' => $attendance->id,
                'payment_attempt_id' => $attempt->id,
                'attendance_status' => $attendance->status,
            ]);

            app(RecordAuditLog::class)->handle(null, 'event_attendance.late_payment_approved', $attendance, [
                'payment_attempt_id' => $attempt->id,
                'amount_cents' => $attempt->amount_cents,
            ]);

            return;
        }

        $attendance->update(['status' => EventAttendance::CONFIRMADA, 'expires_at' => null]);

        app(RecordAuditLog::class)->handle($attendance->customer, 'event_attendance.confirmed', $attendance, [
            'business_id' => $attendance->business_id,
            'payment_attempt_id' => $attempt->id,
        ]);

        app(CreateEventAttendance::class)->notifyConfirmed($attendance);
    }
}
