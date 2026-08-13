<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\ImmersiveEvent;

/**
 * Retención de datos (IMM-043, misma disciplina que `PruneAnalyticsEvents`):
 * elimina eventos inmersivos anteriores a la ventana de retención. Ventana
 * más corta que `analytics_events` (12 meses) porque esta es telemetría de
 * navegación de mayor volumen (cada búsqueda, cada apertura de vitrina),
 * no un registro de negocio que valga la pena conservar tanto tiempo.
 */
class PruneImmersiveEvents
{
    public const DEFAULT_RETENTION_MONTHS = 6;

    public function handle(int $months = self::DEFAULT_RETENTION_MONTHS): int
    {
        return ImmersiveEvent::where('created_at', '<', now()->subMonths($months))->delete();
    }
}
