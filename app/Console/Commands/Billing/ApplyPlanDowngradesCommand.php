<?php

namespace App\Console\Commands\Billing;

use App\Domain\Billing\Actions\ApplyPlanDowngrades;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 4.1 del TODO: baja al plan Gratis las suscripciones canceladas o
 * vencidas cuyo periodo pagado ya terminó. Programado a diario en
 * `routes/console.php`.
 */
#[Signature('billing:apply-plan-downgrades')]
#[Description('Baja al plan Gratis las suscripciones canceladas o vencidas cuyo periodo ya terminó.')]
class ApplyPlanDowngradesCommand extends Command
{
    public function handle(ApplyPlanDowngrades $applyPlanDowngrades): int
    {
        $count = $applyPlanDowngrades->handle();

        $this->info("Negocios bajados al plan Gratis: {$count}.");

        return self::SUCCESS;
    }
}
