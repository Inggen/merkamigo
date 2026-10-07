<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventPaymentAttempt;
use App\Domain\Events\Models\EventReservation;
use Illuminate\Support\Str;

/**
 * Crea (o reutiliza) el intento de pago Wompi de una reserva
 * (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 4). Si ya hay un
 * intento `pendiente` lo reutiliza en vez de crear uno nuevo — evita
 * acumular referencias basura por doble clic en "Continuar al pago" o
 * por recargar la página del widget.
 */
class CreateEventPaymentAttempt
{
    public function handle(EventReservation $reservation): EventPaymentAttempt
    {
        if ($reservation->status !== EventReservation::PENDIENTE_PAGO) {
            throw new EventActionException('Esta reserva ya no admite un nuevo intento de pago.');
        }

        if ($reservation->expires_at && $reservation->expires_at->isPast()) {
            throw new EventActionException('El tiempo para pagar esta reserva venció. Vuelve a cotizar.');
        }

        $pending = $reservation->paymentAttempts()->where('status', EventPaymentAttempt::PENDIENTE)->first();

        if ($pending) {
            return $pending;
        }

        return $reservation->paymentAttempts()->create([
            'reference' => 'MKA-EVT-'.$reservation->business_id.'-'.Str::upper(Str::random(12)),
            'amount_cents' => $reservation->total_cents,
            'currency' => 'COP',
            'status' => EventPaymentAttempt::PENDIENTE,
        ]);
    }
}
