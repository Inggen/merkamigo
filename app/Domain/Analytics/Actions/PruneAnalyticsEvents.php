<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\AnalyticsEvent;

/**
 * Retención de datos (0.6 del TODO): elimina eventos analíticos anteriores
 * a la ventana de retención. `analytics_events` no guarda IP ni user-agent
 * en crudo (solo `visitor_hash`), pero de todas formas se acota su
 * antigüedad para no acumular datos indefinidamente.
 */
class PruneAnalyticsEvents
{
    public const DEFAULT_RETENTION_MONTHS = 12;

    public function handle(int $months = self::DEFAULT_RETENTION_MONTHS): int
    {
        return AnalyticsEvent::where('created_at', '<', now()->subMonths($months))->delete();
    }
}
