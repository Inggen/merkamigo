<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Analytics\Notifications\StorefrontNeedsAttention;
use App\Domain\Businesses\Models\Business;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Detecta vitrinas publicadas que les falta algo básico o que no han
 * tenido actividad en 30 días (4.5 del TODO), y notifica a sus miembros.
 * No reenvía si ya se avisó en los últimos 6 días, para no repetir la
 * misma alerta cada vez que corre el comando semanal.
 */
class DetectIncompleteOrInactiveStorefronts
{
    private const INACTIVITY_DAYS = 30;

    private const RENOTIFY_AFTER_DAYS = 6;

    public function handle(): int
    {
        $businesses = Business::query()
            ->where('status', 'publicado')
            ->with('members', 'products')
            ->get();

        $notified = 0;

        foreach ($businesses as $business) {
            $reasons = $this->reasonsFor($business);

            if ($reasons === [] || $this->recentlyNotified($business)) {
                continue;
            }

            foreach ($business->members as $member) {
                $member->notify(new StorefrontNeedsAttention($business, $reasons));
            }

            $notified++;
        }

        return $notified;
    }

    /**
     * @return array<int, string>
     */
    private function reasonsFor(Business $business): array
    {
        $reasons = [];

        if (blank($business->logo_path)) {
            $reasons[] = __('sin logo');
        }

        if ($business->products->isEmpty()) {
            $reasons[] = __('sin productos');
        }

        if (blank($business->whatsapp_number)) {
            $reasons[] = __('sin número de WhatsApp');
        }

        $lastActivity = AnalyticsEvent::query()
            ->where('business_id', $business->id)
            ->latest('created_at')
            ->value('created_at');

        if ($lastActivity === null || $lastActivity < now()->subDays(self::INACTIVITY_DAYS)) {
            $reasons[] = __('sin actividad en los últimos 30 días');
        }

        return $reasons;
    }

    private function recentlyNotified(Business $business): bool
    {
        return DatabaseNotification::query()
            ->where('type', StorefrontNeedsAttention::class)
            ->where('data->business_id', $business->id)
            ->where('created_at', '>=', now()->subDays(self::RENOTIFY_AFTER_DAYS))
            ->exists();
    }
}
