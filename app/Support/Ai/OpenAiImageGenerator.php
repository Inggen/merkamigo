<?php

namespace App\Support\Ai;

use App\Domain\Platform\Models\OpenAiSetting;
use App\Support\Ai\Contracts\GeneratesImages;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Implementación OpenAI del contrato de generación de imágenes. Usa
 * `image_model` (configuración singleton editable desde admin, separada
 * del modelo de texto) y falla en silencio: cualquier error externo
 * devuelve null para que la app siga sin bloquear al usuario.
 */
class OpenAiImageGenerator implements GeneratesImages
{
    public function generate(string $prompt, array $options = []): ?string
    {
        $settings = OpenAiSetting::current();

        if (! $settings->isEnabled() || blank($settings->imageModel())) {
            return null;
        }

        try {
            $payload = array_filter([
                'model' => $settings->imageModel(),
                'prompt' => $prompt,
                'size' => $options['size'] ?? '1536x1024',
                'n' => 1,
            ], fn ($value) => $value !== null && $value !== '');

            $response = Http::withToken($settings->apiKey())
                ->acceptJson()
                ->asJson()
                ->timeout($settings->timeoutSeconds())
                ->post($settings->baseUrl().'/images/generations', $payload);

            if (! $response->successful()) {
                return null;
            }

            return $this->extractImage($response->json());
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractImage(array $payload): ?string
    {
        $entry = $payload['data'][0] ?? null;

        if (! is_array($entry)) {
            return null;
        }

        $base64 = $entry['b64_json'] ?? null;

        if (is_string($base64) && filled($base64)) {
            $decoded = base64_decode($base64, true);

            return $decoded !== false ? $decoded : null;
        }

        $url = $entry['url'] ?? null;

        if (is_string($url) && filled($url)) {
            return $this->downloadImage($url);
        }

        return null;
    }

    private function downloadImage(string $url): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            return $response->successful() ? $response->body() : null;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
