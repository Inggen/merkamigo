<?php

namespace App\Domain\Marketplace\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Marketplace\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Un cliente pagó un pedido — se envía a los miembros del negocio. El
 * dinero ya está en SU cuenta Wompi, esto es solo el aviso.
 */
class OrderPaid extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

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
            'type' => 'order_paid',
            'order_id' => $this->order->id,
            'product_name' => $this->order->product->name,
            'amount_cents' => $this->order->amount_cents,
            'message' => __('¡Vendiste :product! Te pagaron $:amount directo a tu cuenta.', [
                'product' => $this->order->product->name,
                'amount' => number_format($this->order->amount_cents / 100, 0, ',', '.'),
            ]),
            'url' => route('emprendedores.negocios.ventas', $this->order->business),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('¡Nueva venta!'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
