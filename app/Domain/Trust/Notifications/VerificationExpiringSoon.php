<?php

namespace App\Domain\Trust\Notifications;

use App\Domain\Identity\Notifications\Channels\PushChannel;
use App\Domain\Trust\Models\BusinessVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Recordatorio de renovación de verificación (3.1 del TODO): la
 * verificación de un negocio está por vencer.
 */
class VerificationExpiringSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly BusinessVerification $verification) {}

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
        $business = $this->verification->business;

        return [
            'type' => 'verification_expiring_soon',
            'business_id' => $business->id,
            'business_name' => $business->name,
            'expires_at' => $this->verification->expires_at?->toDateString(),
            'message' => __('La verificación de :business vence el :fecha. Puedes renovarla desde su panel.', [
                'business' => $business->name,
                'fecha' => $this->verification->expires_at?->format('d/m/Y'),
            ]),
            'url' => route('emprendedores.negocios.verificacion', $business),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('Verificación por vencer'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
