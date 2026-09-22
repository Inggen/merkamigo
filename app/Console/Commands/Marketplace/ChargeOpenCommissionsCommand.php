<?php

namespace App\Console\Commands\Marketplace;

use App\Domain\Marketplace\Actions\ChargeOpenCommissions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Cobra en lote las comisiones de Merkamigo por ventas de marketplace
 * (ver TODO-Marketplace-Checkout.md). Programado semanalmente en
 * `routes/console.php`.
 */
#[Signature('marketplace:charge-commissions {--all : Cobrar todas las comisiones abiertas sin importar cuánto llevan abiertas}')]
#[Description('Cobra las comisiones de marketplace acumuladas contra la tarjeta guardada de cada negocio.')]
class ChargeOpenCommissionsCommand extends Command
{
    public function handle(ChargeOpenCommissions $chargeOpenCommissions): int
    {
        $minAgeDays = $this->option('all') ? 0 : 7;

        $count = $chargeOpenCommissions->handle($minAgeDays);

        $this->info("Comisiones cobradas: {$count}.");

        return self::SUCCESS;
    }
}
