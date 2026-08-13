<?php

namespace App\Console\Commands\Analytics;

use App\Domain\Analytics\Actions\PruneImmersiveEvents;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * IMM-043: política de retención de `immersive_events`. Programado
 * semanalmente en `routes/console.php`, mismo patrón que
 * `analytics:prune-events`.
 */
#[Signature('immersive-events:prune {--months= : Meses de retención a conservar}')]
#[Description('Elimina eventos de plazas inmersivas más antiguos que la ventana de retención.')]
class PruneImmersiveEventsCommand extends Command
{
    public function handle(PruneImmersiveEvents $pruneImmersiveEvents): int
    {
        $months = $this->option('months') !== null
            ? (int) $this->option('months')
            : PruneImmersiveEvents::DEFAULT_RETENTION_MONTHS;

        $count = $pruneImmersiveEvents->handle($months);

        $this->info("Eventos inmersivos eliminados: {$count}.");

        return self::SUCCESS;
    }
}
