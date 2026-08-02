<?php

namespace App\Domain\Identity\Notifications\Channels;

use App\Models\User;
use App\Support\Push\FcmClient;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Canal de notificaciones push (5.2 del TODO). Respeta las preferencias
 * del usuario y aísla fallos por dispositivo — un token inválido no debe
 * tumbar el resto del envío ni la notificación completa (los otros
 * canales, como `database`, ya se procesaron de forma independiente).
 */
class PushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User || ! method_exists($notification, 'toPush')) {
            return;
        }

        if ($notifiable->hasDisabledPushFor($notification::class)) {
            return;
        }

        $payload = $notification->toPush($notifiable);

        foreach ($notifiable->devices as $device) {
            try {
                app(FcmClient::class)->send($device->push_token, $payload);
            } catch (Throwable) {
                // Un dispositivo con token vencido/inválido no debe impedir
                // que los demás dispositivos del usuario reciban el push.
            }
        }
    }
}
