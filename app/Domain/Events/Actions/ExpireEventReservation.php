<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Models\EventReservation;
use Illuminate\Support\Facades\DB;

/**
 * Libera la retención de una reserva vencida sin pago
 * (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 3/4) — mismo
 * criterio que `CancelLoyaltyRedemption` para canjes vencidos: tarea
 * programada idempotente, bloquea la fila antes de decidir para no
 * chocar con un pago que se esté confirmando al mismo tiempo.
 */
class ExpireEventReservation
{
    public function handle(EventReservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            /** @var EventReservation|null $locked */
            $locked = EventReservation::whereKey($reservation->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== EventReservation::PENDIENTE_PAGO) {
                return;
            }

            if ($locked->expires_at && $locked->expires_at->isFuture()) {
                return;
            }

            $locked->update(['status' => EventReservation::VENCIDA]);
        });
    }
}
