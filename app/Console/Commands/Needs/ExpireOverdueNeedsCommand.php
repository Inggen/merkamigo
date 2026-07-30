<?php

namespace App\Console\Commands\Needs;

use App\Domain\Needs\Actions\ExpireOverdueNeeds;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 2.1/2.3 del TODO: cierra por vencimiento las necesidades cuya fecha de
 * expiración ya pasó. Programado a diario en `routes/console.php`.
 */
#[Signature('needs:expire-overdue')]
#[Description('Cierra por vencimiento las necesidades publicadas que ya expiraron.')]
class ExpireOverdueNeedsCommand extends Command
{
    public function handle(ExpireOverdueNeeds $expireOverdueNeeds): int
    {
        $count = $expireOverdueNeeds->handle();

        $this->info("Necesidades vencidas cerradas: {$count}.");

        return self::SUCCESS;
    }
}
