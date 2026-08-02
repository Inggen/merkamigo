<?php

namespace App\Domain\Platform\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Suscripción a webhooks salientes firmados para aliados (5.4 del TODO).
 * `business_id` nulo significa una suscripción de plataforma (recibe el
 * evento sin importar el negocio); con `business_id`, solo eventos de
 * ese negocio.
 *
 * @property array<int, string>|null $subscribed_events
 */
class WebhookSubscription extends Model
{
    protected $fillable = ['business_id', 'url', 'secret', 'subscribed_events', 'is_active'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'subscribed_events' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function matchesEvent(string $action, ?int $businessId): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! in_array($action, $this->subscribed_events ?? [], true)) {
            return false;
        }

        return $this->business_id === null || $this->business_id === $businessId;
    }
}
