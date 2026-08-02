<?php

namespace App\Support\Ai;

use App\Domain\Platform\Models\OpenAiSetting;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Implementación OpenAI del contrato de texto asistido. Usa la
 * configuración singleton editable desde admin y falla en silencio:
 * cualquier error externo devuelve null para que la app siga con su
 * fallback no asistido.
 */
class OpenAiTextGenerator implements GeneratesAssistedText
{
    public function generate(string $prompt, array $context = []): ?string
    {
        $settings = OpenAiSetting::current();

        if (! $settings->isEnabled()) {
            return null;
        }

        try {
            $payload = array_filter([
                'model' => $settings->model(),
                'input' => $this->buildInput($prompt, $context),
                'instructions' => $settings->systemPrompt(),
                'temperature' => $settings->temperature(),
                'max_output_tokens' => $settings->maxOutputTokens(),
            ], fn ($value) => $value !== null && $value !== '');

            $response = Http::withToken($settings->apiKey())
                ->acceptJson()
                ->asJson()
                ->timeout($settings->timeoutSeconds())
                ->post($settings->baseUrl().'/responses', $payload);

            if (! $response->successful()) {
                return null;
            }

            return $this->extractText($response->json());
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildInput(string $prompt, array $context): string
    {
        if ($context === []) {
            return $prompt;
        }

        return trim($prompt."\n\nContexto real disponible:\n".json_encode(
            $context,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractText(array $payload): ?string
    {
        $direct = $payload['output_text'] ?? null;

        if (is_string($direct) && filled(trim($direct))) {
            return trim($direct);
        }

        $chunks = [];

        foreach (($payload['output'] ?? []) as $outputItem) {
            foreach (($outputItem['content'] ?? []) as $contentItem) {
                $text = $contentItem['text'] ?? null;

                if (is_string($text) && filled(trim($text))) {
                    $chunks[] = trim($text);
                }
            }
        }

        $text = trim(implode("\n\n", $chunks));

        return filled($text) ? $text : null;
    }
}
