<?php

namespace App\Domain\Loyalty\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * TODO_Merkapuntos.md, F2.6: "vencimiento/cancelación: 'Tus puntos están
 * disponibles de nuevo'."
 */
class RedemptionPointsReleased extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly LoyaltyRedemption $redemption) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', PushChannel::class];
    }

    /**
     * @return array{type: string, redemption_id: int, message: string, url: string, action_label: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'loyalty_redemption_released',
            'redemption_id' => $this->redemption->id,
            'message' => __('Tus puntos están disponibles de nuevo (:points Merkapuntos)', [
                'points' => $this->redemption->points_reserved,
            ]),
            'url' => route('clientes.actividad'),
            'action_label' => __('Ver'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return [
            'title' => __('Puntos liberados'),
            'body' => $data['message'],
            'url' => $data['url'],
        ];
    }
}
