<?php

namespace App\Domain\Needs\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Needs\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Un negocio retiró la propuesta que le había enviado al comprador (2.2 del
 * TODO). Alimenta el Centro de actividad del Cliente (0.2.2/1.1.1 del TODO).
 */
class OfferWithdrawn extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Offer $offer) {}

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
        $need = $this->offer->need;

        return [
            'type' => 'offer_withdrawn',
            'need_id' => $need->id,
            'need_title' => $need->title,
            'offer_id' => $this->offer->id,
            'business_name' => $this->offer->business->name,
            'message' => __(':business retiró su propuesta para tu solicitud ":need".', [
                'business' => $this->offer->business->name,
                'need' => $need->title,
            ]),
            'url' => route('mis-solicitudes.show', $need),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('Propuesta retirada'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
