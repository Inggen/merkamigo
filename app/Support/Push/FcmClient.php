<?php

namespace App\Support\Push;

use Closure;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Envío de notificaciones push vía Firebase Cloud Messaging (5.2 del
 * TODO). Mismo patrón que `App\Support\Wompi\WompiClient`: integración
 * real por HTTP, credenciales de prueba en `phpunit.xml` — sin push
 * reales hasta que se configuren credenciales de producción.
 */
class FcmClient
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private ?string $accessToken = null;

    public function __construct(private readonly ?Closure $accessTokenResolver = null) {}

    /**
     * @param  array{title: string, body: string, url?: string}  $notification
     * @return bool true si FCM aceptó el envío.
     */
    public function send(string $pushToken, array $notification): bool
    {
        $projectId = (string) config('services.fcm.project_id');

        if ($projectId === '') {
            throw new RuntimeException('Falta configurar FCM_PROJECT_ID.');
        }

        $response = Http::withToken($this->token())
            ->post(rtrim((string) config('services.fcm.endpoint'), '/')."/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $pushToken,
                    'notification' => [
                        'title' => $notification['title'],
                        'body' => $notification['body'],
                    ],
                    'data' => [
                        'url' => $notification['url'] ?? url('/'),
                    ],
                    'webpush' => [
                        'fcm_options' => [
                            'link' => $notification['url'] ?? url('/'),
                        ],
                        'notification' => [
                            'icon' => asset('icons/icon-192.png'),
                            'badge' => asset('icons/icon-192.png'),
                        ],
                    ],
                ],
            ]);

        return $response->successful();
    }

    private function token(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        if ($this->accessTokenResolver !== null) {
            return $this->accessToken = ($this->accessTokenResolver)();
        }

        return $this->accessToken = Cache::remember(
            'fcm.access_token.'.config('services.fcm.project_id'),
            now()->addMinutes(50),
            function (): string {
                $configuredPath = (string) config('services.fcm.credentials');
                $path = str_starts_with($configuredPath, DIRECTORY_SEPARATOR)
                    ? $configuredPath
                    : base_path($configuredPath);
                $contents = is_readable($path) ? file_get_contents($path) : false;

                if ($contents === false) {
                    throw new RuntimeException('No fue posible cargar las credenciales de Firebase.');
                }

                $credentials = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
                $token = (new ServiceAccountCredentials(self::SCOPE, $credentials))->fetchAuthToken();
                $accessToken = $token['access_token'] ?? null;

                if (! is_string($accessToken) || $accessToken === '') {
                    throw new RuntimeException('Firebase no devolvió un token de acceso válido.');
                }

                return $accessToken;
            },
        );
    }
}
