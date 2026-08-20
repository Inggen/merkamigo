<?php

namespace App\Console\Commands\Billing;

use App\Domain\Billing\Actions\ProcessSubscriptionRenewals;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 4.2 del TODO: cobra la renovación mensual a los negocios con tarjeta
 * guardada y mete a periodo de gracia a los que vencieron sin renovar.
 * Programado a diario en `routes/console.php`, justo antes de
 * `billing:apply-plan-downgrades`.
 */
#[Signature('billing:process-subscription-renewals')]
#[Description('Cobra la renovación automática de planes vencidos y marca en gracia a los que no renovaron.')]
class ProcessSubscriptionRenewalsCommand extends Command
{
    public function handle(ProcessSubscriptionRenewals $processSubscriptionRenewals): int
    {
        $count = $processSubscriptionRenewals->handle();

        $this->info("Suscripciones procesadas: {$count}.");

        return self::SUCCESS;
    }
}
