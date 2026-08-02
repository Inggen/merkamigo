<?php

namespace App\Console\Commands\Analytics;

use App\Domain\Analytics\Actions\SendWeeklyBusinessReports;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 4.5 del TODO: informe semanal por correo. Programado semanalmente en
 * `routes/console.php`.
 */
#[Signature('analytics:send-weekly-reports')]
#[Description('Envía el informe semanal de métricas a los negocios publicados con actividad.')]
class SendWeeklyBusinessReportsCommand extends Command
{
    public function handle(SendWeeklyBusinessReports $sendWeeklyBusinessReports): int
    {
        $count = $sendWeeklyBusinessReports->handle();

        $this->info("Informes semanales enviados: {$count}.");

        return self::SUCCESS;
    }
}
