<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Models\EventAttendance;
use Illuminate\Support\Facades\DB;

/**
 * Libera el cupo de una reserva de asistencia de pago que venció sin
 * confirmar — mismo criterio que `ExpireEventReservation`.
 */
class ExpireEventAttendance
{
    public function handle(EventAttendance $attendance): void
    {
        DB::transaction(function () use ($attendance) {
            /** @var EventAttendance|null $locked */
            $locked = EventAttendance::whereKey($attendance->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== EventAttendance::PENDIENTE_PAGO) {
                return;
            }

            if ($locked->expires_at && $locked->expires_at->isFuture()) {
                return;
            }

            $locked->update(['status' => EventAttendance::VENCIDA]);
        });
    }
}
