<?php

namespace App\Support\Push;

use Illuminate\Support\Facades\Http;

/**
 * Envío de notificaciones push vía Firebase Cloud Messaging (5.2 del
 * TODO). Mismo patrón que `App\Support\Wompi\WompiClient`: integración
 * real por HTTP, credenciales de prueba en `phpunit.xml` — sin push
 * reales hasta que se configuren credenciales de producción.
 */
class FcmClient
{
    /**
     * @param  array{title: string, body: string, url?: string}  $notification
     * @return bool true si FCM aceptó el envío.
     */
    public function send(string $pushToken, array $notification): bool
    {
        $response = Http::withToken(config('services.fcm.server_key'))
            ->post(config('services.fcm.endpoint'), [
                'to' => $pushToken,
                'notification' => [
                    'title' => $notification['title'],
                    'body' => $notification['body'],
                ],
                'data' => [
                    'url' => $notification['url'] ?? null,
                ],
            ]);

        return $response->successful();
    }
}
