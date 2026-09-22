<?php

namespace App\Console\Commands\Subscriptions;

use App\Domain\Subscriptions\Actions\RenewCustomerSubscriptions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Cobra el primer periodo real de las suscripciones cuya prueba venció, y
 * renueva las que ya cumplieron su periodo pagado (Fase 8.2 del TODO
 * social). Programado a diario en `routes/console.php`.
 */
#[Signature('subscriptions:renew')]
#[Description('Cobra y renueva las suscripciones de clientes a productos de negocios.')]
class RenewCustomerSubscriptionsCommand extends Command
{
    public function handle(RenewCustomerSubscriptions $renewCustomerSubscriptions): int
    {
        $count = $renewCustomerSubscriptions->handle();

        $this->info("Suscripciones procesadas: {$count}.");

        return self::SUCCESS;
    }
}
