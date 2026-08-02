<?php

namespace App\Console\Commands\Analytics;

use App\Domain\Analytics\Actions\PruneAnalyticsEvents;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 0.6 del TODO: política de retención de `analytics_events`. Programado
 * semanalmente en `routes/console.php`.
 */
#[Signature('analytics:prune-events {--months= : Meses de retención a conservar}')]
#[Description('Elimina eventos analíticos más antiguos que la ventana de retención.')]
class PruneAnalyticsEventsCommand extends Command
{
    public function handle(PruneAnalyticsEvents $pruneAnalyticsEvents): int
    {
        $months = $this->option('months') !== null
            ? (int) $this->option('months')
            : PruneAnalyticsEvents::DEFAULT_RETENTION_MONTHS;

        $count = $pruneAnalyticsEvents->handle($months);

        $this->info("Eventos de analítica eliminados: {$count}.");

        return self::SUCCESS;
    }
}
