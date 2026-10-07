<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventAttendance;
use App\Domain\Events\Models\EventAttendancePaymentAttempt;
use Illuminate\Support\Str;

/**
 * Crea (o reutiliza) el intento de pago Wompi de una reserva de
 * asistencia — mismo patrón que `CreateEventPaymentAttempt` (reservas de
 * espacio).
 */
class CreateEventAttendancePaymentAttempt
{
    public function handle(EventAttendance $attendance): EventAttendancePaymentAttempt
    {
        if ($attendance->status !== EventAttendance::PENDIENTE_PAGO) {
            throw new EventActionException('Esta reserva ya no admite un nuevo intento de pago.');
        }

        if ($attendance->expires_at && $attendance->expires_at->isPast()) {
            throw new EventActionException('El tiempo para pagar esta reserva venció. Vuelve a reservar tu cupo.');
        }

        $pending = $attendance->paymentAttempts()->where('status', EventAttendancePaymentAttempt::PENDIENTE)->first();

        if ($pending) {
            return $pending;
        }

        return $attendance->paymentAttempts()->create([
            'reference' => 'MKA-EVA-'.$attendance->business_id.'-'.Str::upper(Str::random(12)),
            'amount_cents' => $attendance->total_cents,
            'currency' => 'COP',
            'status' => EventAttendancePaymentAttempt::PENDIENTE,
        ]);
    }
}
