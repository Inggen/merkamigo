<?php

namespace App\Domain\Analytics\Notifications;

use App\Domain\Businesses\Models\Business;
use App\Domain\Identity\Notifications\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Alerta de vitrina incompleta o inactiva (4.5 del TODO): la vitrina le
 * falta algo básico (logo, productos, WhatsApp) o no ha tenido ninguna
 * actividad en 30 días.
 */
class StorefrontNeedsAttention extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, string>  $reasons
     */
    public function __construct(private readonly Business $business, private readonly array $reasons) {}

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
            'type' => 'storefront_needs_attention',
            'business_id' => $this->business->id,
            'business_name' => $this->business->name,
            'reasons' => $this->reasons,
            'message' => __('Tu vitrina :business necesita atención: :reasons.', [
                'business' => $this->business->name,
                'reasons' => implode(', ', $this->reasons),
            ]),
            'url' => route('emprendedores.negocios.vitrina', $this->business),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('Tu vitrina necesita atención'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
