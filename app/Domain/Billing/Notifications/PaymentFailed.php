<?php

namespace App\Domain\Billing\Notifications;

use App\Domain\Billing\Models\Payment;
use App\Domain\Identity\Notifications\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Un pago fue declinado o falló (4.2 del TODO). Notifica al equipo del
 * negocio para que pueda reintentar con otro medio de pago.
 */
class PaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Payment $payment) {}

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
            'type' => 'payment_failed',
            'payment_id' => $this->payment->id,
            'message' => __('No pudimos procesar tu pago de :monto. Intenta de nuevo con otro medio de pago.', [
                'monto' => '$'.number_format($this->payment->amount_cents / 100, 0, ',', '.').' COP',
            ]),
            'url' => route('emprendedores.negocios.plan', $this->payment->business_id),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('Pago fallido'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
