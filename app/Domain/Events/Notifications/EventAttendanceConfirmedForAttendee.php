<?php

namespace App\Domain\Events\Notifications;

use App\Domain\Events\Models\EventAttendance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Confirmación al asistente con el enlace a su entrada (QR de ingreso).
 * El asistente puede no tener cuenta — se envía por correo con
 * `Notification::route('mail', ...)` (on-demand), igual que
 * `EventReservationConfirmedForProspect`. El enlace es una URL firmada
 * (`signed`): solo quien recibió este correo puede abrir la entrada, no
 * cualquiera que adivine el id.
 */
class EventAttendanceConfirmedForAttendee extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly EventAttendance $attendance) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $attendance = $this->attendance->fresh(['publicEvent.business']);
        $event = $attendance->publicEvent;

        $ticketUrl = URL::signedRoute('eventos.entradas.show', ['eventAttendance' => $attendance->id]);

        $message = (new MailMessage)
            ->subject(__('Tu entrada para :event', ['event' => $event->title]))
            ->greeting(__('¡Hola :name!', ['name' => $attendance->attendee_name]))
            ->line(__('Tu cupo para :event quedó confirmado.', ['event' => $event->title]))
            ->line(__('Fecha: :date', ['date' => $event->starts_at->translatedFormat('l j \d\e F \d\e Y, g:i a')]))
            ->line(__('Cupos: :quantity', ['quantity' => $attendance->quantity]));

        if (! $attendance->isFree()) {
            $message->line(__('Total pagado: $:amount', ['amount' => number_format($attendance->total_cents / 100, 0, ',', '.')]));
        }

        return $message
            ->action(__('Ver mi entrada'), $ticketUrl)
            ->line(__('Muestra el código QR de tu entrada al llegar — :business lo escanea para confirmar tu ingreso.', ['business' => $event->business->name]));
    }
}
