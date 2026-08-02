<?php

namespace App\Domain\Platform\Jobs;

use App\Domain\Platform\Models\WebhookSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Entrega un webhook saliente firmado (5.4 del TODO) — firma HMAC-SHA256
 * del cuerpo exacto enviado, mismo criterio que se usa para verificar la
 * firma entrante de Wompi. Reintenta con backoff creciente; si las tres
 * entregas fallan, la suscripción sigue activa (no se desactiva sola) —
 * queda para que un administrador revise manualmente vía Filament.
 */
class SendOutboundWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        private readonly int $subscriptionId,
        private readonly string $event,
        private readonly ?string $subjectType,
        private readonly int|string|null $subjectId,
        private readonly array $metadata,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        $subscription = WebhookSubscription::find($this->subscriptionId);

        if (! $subscription || ! $subscription->is_active) {
            return;
        }

        $payload = json_encode([
            'event' => $this->event,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'metadata' => $this->metadata,
            'timestamp' => now()->timestamp,
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $payload, $subscription->secret);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Merkamigo-Signature' => $signature,
        ])->withBody($payload, 'application/json')->post($subscription->url);

        if (! $response->successful()) {
            throw new RuntimeException("Webhook a {$subscription->url} respondió {$response->status()}.");
        }
    }
}
