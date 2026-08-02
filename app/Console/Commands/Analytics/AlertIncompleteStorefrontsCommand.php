<?php

namespace App\Console\Commands\Analytics;

use App\Domain\Analytics\Actions\DetectIncompleteOrInactiveStorefronts;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 4.5 del TODO: alerta a vitrinas incompletas o inactivas. Programado
 * semanalmente en `routes/console.php`.
 */
#[Signature('analytics:alert-incomplete-storefronts')]
#[Description('Notifica a los negocios con vitrinas incompletas o sin actividad en 30 días.')]
class AlertIncompleteStorefrontsCommand extends Command
{
    public function handle(DetectIncompleteOrInactiveStorefronts $detectIncompleteOrInactiveStorefronts): int
    {
        $count = $detectIncompleteOrInactiveStorefronts->handle();

        $this->info("Negocios notificados: {$count}.");

        return self::SUCCESS;
    }
}
