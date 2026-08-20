<?php

namespace App\Domain\Billing\Notifications;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Identity\Notifications\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * El plan de pago de un negocio venció y no tiene tarjeta guardada para
 * renovarse solo (4.2 del TODO) — le queda el periodo de gracia de
 * `ProcessSubscriptionRenewals` para pagar manual antes de bajar a Gratis.
 */
class SubscriptionRenewalDue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Subscription $subscription) {}

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
            'type' => 'subscription_renewal_due',
            'subscription_id' => $this->subscription->id,
            'message' => __('Tu plan :plan venció. Renuévalo antes de :fecha o volverás al plan Gratis.', [
                'plan' => $this->subscription->plan->name,
                'fecha' => $this->subscription->grace_ends_at?->translatedFormat('d M') ?? '',
            ]),
            'url' => route('emprendedores.negocios.plan', $this->subscription->business_id),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('Tu plan está por vencer'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
