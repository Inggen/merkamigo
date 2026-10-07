<?php

namespace App\Domain\Events\Notifications;

use App\Domain\Events\Models\EventReservation;
use App\Domain\Identity\Notifications\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Aviso interno al negocio: se confirmó una reserva de evento (pago
 * verificado). Mismo patrón que `Marketplace\Notifications\OrderPaid`.
 */
class EventReservationConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly EventReservation $reservation) {}

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
        return [
            'type' => 'event_reservation_confirmed',
            'reservation_id' => $this->reservation->id,
            'prospect_name' => $this->reservation->prospect_name,
            'starts_at' => $this->reservation->starts_at->toIso8601String(),
            'party_size' => $this->reservation->party_size,
            'amount_cents' => $this->reservation->total_cents,
            'message' => __(':name reservó un evento para :date — $:amount confirmados.', [
                'name' => $this->reservation->prospect_name,
                'date' => $this->reservation->starts_at->translatedFormat('d M, g:i a'),
                'amount' => number_format($this->reservation->total_cents / 100, 0, ',', '.'),
            ]),
            'url' => route('emprendedores.negocios.eventos', $this->reservation->business_id),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('¡Nueva reserva de evento!'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
