<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Jobs\SendOutboundWebhook;
use App\Domain\Platform\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Model;

/**
 * Despacha webhooks salientes para un evento de dominio (5.4 del TODO).
 * Se engancha desde `RecordAuditLog::handle()` en vez de tocar cada
 * acción de dominio una por una — cualquier acción que ya audita algo
 * queda automáticamente cubierta si su nombre está en el listado
 * curado de eventos externos.
 */
class DispatchOutboundWebhook
{
    /**
     * Acciones de auditoría que sí importan a un aliado externo. No todo
     * lo que se audita es relevante hacia afuera (ej. `auth.login`,
     * `business.member_role_updated` son internos).
     *
     * @var array<int, string>
     */
    public const SUBSCRIBABLE_EVENTS = [
        'business.published',
        'business.unpublished',
        'business.suspended',
        'business.verification_reviewed',
        'need.published',
        'need.closed',
        'offer.submitted',
        'offer.withdrawn',
        'order.confirmed_by_customer',
        'order.confirmed_by_business',
        'order.completed',
        'order.cancelled',
        'order.disputed',
    ];

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(string $action, ?Model $subject, array $metadata = []): void
    {
        if (! in_array($action, self::SUBSCRIBABLE_EVENTS, true)) {
            return;
        }

        $businessId = $this->resolveBusinessId($subject, $metadata);

        $subscriptions = WebhookSubscription::where('is_active', true)->get()
            ->filter(fn (WebhookSubscription $subscription) => $subscription->matchesEvent($action, $businessId));

        foreach ($subscriptions as $subscription) {
            SendOutboundWebhook::dispatch(
                $subscription->id,
                $action,
                $subject?->getMorphClass(),
                $subject?->getKey(),
                $metadata,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function resolveBusinessId(?Model $subject, array $metadata): ?int
    {
        if (isset($metadata['business_id'])) {
            return (int) $metadata['business_id'];
        }

        if ($subject instanceof Business) {
            return $subject->id;
        }

        if ($subject && isset($subject->business_id)) {
            return (int) $subject->business_id;
        }

        return null;
    }
}
