<?php

namespace App\Console\Commands\Events;

use App\Domain\Events\Actions\ExpireEventReservation;
use App\Domain\Events\Models\EventReservation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 3/4: libera la
 * retención de las reservas `pendiente_pago` cuya franja venció sin pago
 * confirmado. Programado en `routes/console.php`.
 */
#[Signature('events:expire-reservations')]
#[Description('Marca como vencidas las reservas de eventos pendientes de pago cuya retención expiró.')]
class ExpireEventReservationsCommand extends Command
{
    public function handle(ExpireEventReservation $expireEventReservation): int
    {
        $expired = EventReservation::query()
            ->where('status', EventReservation::PENDIENTE_PAGO)
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $reservation) {
            $expireEventReservation->handle($reservation);
        }

        $this->info("Reservas de eventos vencidas: {$expired->count()}.");

        return self::SUCCESS;
    }
}
