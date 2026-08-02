<?php

namespace App\Domain\Trust\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Trust\Models\OrderConfirmation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * El negocio confirmó su lado de un pedido (2.3 del TODO), quedando
 * confirmado por ambas partes. Alimenta el Centro de actividad del Cliente
 * (0.2.2/1.1.1 del TODO).
 */
class OrderConfirmedByBusiness extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly OrderConfirmation $order) {}

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
            'type' => 'order_confirmed_by_business',
            'order_id' => $this->order->id,
            'business_name' => $this->order->business->name,
            'message' => __(':business confirmó tu compra ":resumen".', [
                'business' => $this->order->business->name,
                'resumen' => $this->order->summary,
            ]),
            'url' => route('clientes.pedidos'),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('Pedido confirmado'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
