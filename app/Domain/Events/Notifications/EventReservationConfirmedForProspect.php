<?php

namespace App\Domain\Events\Notifications;

use App\Domain\Events\Models\EventReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmación al prospecto (Fase 4: "incluir fecha/hora, asistentes,
 * platos, equipos, total y referencia"). El prospecto puede no tener
 * cuenta — se envía por correo con `Notification::route('mail', ...)`
 * (on-demand), no como notificación de un `User`.
 */
class EventReservationConfirmedForProspect extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly EventReservation $reservation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reservation = $this->reservation->fresh(['business', 'dishes', 'equipment']);

        $message = (new MailMessage)
            ->subject(__('Tu evento en :business está confirmado', ['business' => $reservation->business->name]))
            ->greeting(__('¡Hola :name!', ['name' => $reservation->prospect_name]))
            ->line(__('Tu reserva en :business quedó confirmada.', ['business' => $reservation->business->name]))
            ->line(__('Fecha: :date', ['date' => $reservation->starts_at->translatedFormat('l j \d\e F \d\e Y, g:i a')]))
            ->line(__('Duración: :hours hora(s)', ['hours' => $reservation->duration_hours]))
            ->line(__('Personas: :count', ['count' => $reservation->party_size]));

        foreach ($reservation->dishes as $dish) {
            $message->line('· '.$dish->quantity.'× '.$dish->name_snapshot);
        }

        foreach ($reservation->equipment as $equipment) {
            $message->line('· '.__('Equipo').': '.$equipment->name_snapshot);
        }

        return $message
            ->line(__('Total pagado: $:amount', ['amount' => number_format($reservation->total_cents / 100, 0, ',', '.')]))
            ->line(__('Referencia: :reference', ['reference' => $reservation->idempotency_key]))
            ->line(__('Cualquier duda, escribe directo a :business.', ['business' => $reservation->business->name]));
    }
}
