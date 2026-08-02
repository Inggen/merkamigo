<?php

namespace App\Console\Commands\Trust;

use App\Domain\Trust\Actions\SendVerificationExpiryReminders;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 3.1 del TODO: recordatorios de renovación de verificación. Programado a
 * diario en `routes/console.php`.
 */
#[Signature('trust:remind-verification-expiry')]
#[Description('Notifica a los negocios cuya verificación vigente está por vencer.')]
class SendVerificationExpiryRemindersCommand extends Command
{
    public function handle(SendVerificationExpiryReminders $sendVerificationExpiryReminders): int
    {
        $count = $sendVerificationExpiryReminders->handle();

        $this->info("Recordatorios de vencimiento enviados: {$count}.");

        return self::SUCCESS;
    }
}
