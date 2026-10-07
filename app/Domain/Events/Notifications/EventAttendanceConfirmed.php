<?php

namespace App\Domain\Events\Notifications;

use App\Domain\Events\Models\EventAttendance;
use App\Domain\Identity\Notifications\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Aviso interno al negocio: alguien reservó cupo para uno de sus eventos
 * públicos. Mismo patrón que `EventReservationConfirmed` (reservas de
 * espacio).
 */
class EventAttendanceConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly EventAttendance $attendance) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', PushChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $event = $this->attendance->publicEvent;

        return [
            'type' => 'event_attendance_confirmed',
            'attendance_id' => $this->attendance->id,
            'public_event_id' => $this->attendance->public_event_id,
            'attendee_name' => $this->attendance->attendee_name,
            'quantity' => $this->attendance->quantity,
            'message' => __(':name reservó :quantity cupo(s) para ":event".', [
                'name' => $this->attendance->attendee_name,
                'quantity' => $this->attendance->quantity,
                'event' => $event?->title,
            ]),
            'url' => route('emprendedores.negocios.eventos', $this->attendance->business_id),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('¡Nueva reserva de cupo!'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
