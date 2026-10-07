<?php

namespace App\Console\Commands\Events;

use App\Domain\Events\Actions\ExpireEventAttendance;
use App\Domain\Events\Models\EventAttendance;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Libera el cupo de las reservas de asistencia `pendiente_pago` cuya
 * retención expiró. Programado en `routes/console.php`.
 */
#[Signature('events:expire-attendances')]
#[Description('Marca como vencidas las reservas de cupo a eventos pendientes de pago cuya retención expiró.')]
class ExpireEventAttendancesCommand extends Command
{
    public function handle(ExpireEventAttendance $expireEventAttendance): int
    {
        $expired = EventAttendance::query()
            ->where('status', EventAttendance::PENDIENTE_PAGO)
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $attendance) {
            $expireEventAttendance->handle($attendance);
        }

        $this->info("Reservas de cupo vencidas: {$expired->count()}.");

        return self::SUCCESS;
    }
}
