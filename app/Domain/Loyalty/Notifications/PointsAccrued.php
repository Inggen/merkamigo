<?php

namespace App\Domain\Loyalty\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Loyalty\Models\LoyaltyPurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * TODO_Merkapuntos.md, F2.6: "Compra confirmada: 'Ganaste N Merkapuntos en
 * X'." Se envía siempre después del commit de `RegisterLoyaltyPurchase`,
 * nunca dentro de la misma transacción.
 */
class PointsAccrued extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly LoyaltyPurchase $purchase) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', PushChannel::class];
    }

    /**
     * @return array{type: string, purchase_id: int, business_id: int, message: string, url: string, action_label: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'loyalty_points_accrued',
            'purchase_id' => $this->purchase->id,
            'business_id' => $this->purchase->business_id,
            'message' => __('Ganaste :points Merkapuntos en :business', [
                'points' => $this->purchase->points_awarded,
                'business' => $this->purchase->business->name,
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
            'title' => __('¡Ganaste Merkapuntos!'),
            'body' => $data['message'],
            'url' => $data['url'],
        ];
    }
}
