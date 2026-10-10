<?php

namespace App\Console\Commands\Billing;

use App\Domain\Billing\Actions\ProcessEntitlementRenewals;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * PR4 de TODO_VENTAS_RENTABILIDAD.md: cobra la renovación mensual de
 * los add-ons tipo entitlement recurrentes (ej. el asistente IA) a los
 * negocios con tarjeta guardada. Programado a diario en
 * `routes/console.php`.
 */
#[Signature('billing:process-entitlement-renewals')]
#[Description('Cobra la renovación automática de add-ons recurrentes (ej. asistente IA) vencidos.')]
class ProcessEntitlementRenewalsCommand extends Command
{
    public function handle(ProcessEntitlementRenewals $processEntitlementRenewals): int
    {
        $count = $processEntitlementRenewals->handle();

        $this->info("Add-ons renovados: {$count}.");

        return self::SUCCESS;
    }
}
