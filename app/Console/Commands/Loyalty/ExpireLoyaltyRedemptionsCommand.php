<?php

namespace App\Console\Commands\Loyalty;

use App\Domain\Loyalty\Actions\CancelLoyaltyRedemption;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * TODO_Merkapuntos.md, F1.6: "Tarea programada idempotente libera lo
 * vencido." Programado en `routes/console.php`.
 */
#[Signature('loyalty:expire-redemptions')]
#[Description('Libera puntos, stock y presupuesto de los canjes de Merkapuntos reservados que vencieron sin entrega.')]
class ExpireLoyaltyRedemptionsCommand extends Command
{
    public function handle(CancelLoyaltyRedemption $cancelLoyaltyRedemption): int
    {
        $expired = LoyaltyRedemption::query()
            ->where('status', LoyaltyRedemption::RESERVADO)
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $redemption) {
            $cancelLoyaltyRedemption->handle($redemption, CancelLoyaltyRedemption::RAZON_VENCIDO);
        }

        $this->info("Canjes vencidos liberados: {$expired->count()}.");

        return self::SUCCESS;
    }
}
