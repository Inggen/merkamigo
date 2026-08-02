<?php

namespace App\Domain\Trust\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Trust\Models\OrderConfirmation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Un negocio registró un pedido directo contigo (3.2 del TODO: "constancia
 * desde... contacto") y espera tu confirmación desde "Mis pedidos".
 */
class OrderPendingYourConfirmation extends Notification implements ShouldQueue
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
        $business = $this->order->business;

        return [
            'type' => 'order_pending_your_confirmation',
            'order_id' => $this->order->id,
            'business_name' => $business->name,
            'message' => __(':business registró un pedido contigo: ":resumen". Confírmalo si es correcto.', [
                'business' => $business->name,
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

        return ['title' => __('Pedido pendiente de confirmar'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
