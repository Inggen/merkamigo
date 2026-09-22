<?php

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Models\CommissionCharge;
use Illuminate\Support\Facades\Log;

/**
 * Cobra en lote las comisiones abiertas (pendiente #3 de
 * TODO-Marketplace-Checkout.md, resuelto en esta sesión) — corre
 * semanalmente (`routes/console.php`), mismo criterio de "por lotes" que
 * se decidió con el usuario: menos tarifas de payout pagadas que cobrar
 * venta por venta, y da un colchón natural para reembolsos antes de que
 * el dinero salga de la tarjeta del negocio.
 *
 * Solo cobra comisiones con al menos `$minAgeDays` desde que se abrieron
 * (por defecto 7) — evita cobrar una comisión que se acaba de abrir si
 * el comando se corre a mano más seguido de lo programado.
 */
class ChargeOpenCommissions
{
    public function handle(int $minAgeDays = 7): int
    {
        $charged = 0;

        CommissionCharge::query()
            ->where('status', CommissionCharge::ABIERTA)
            ->where('commission_cents', '>', 0)
            ->where('period_start', '<=', now()->subDays($minAgeDays))
            ->with('business')
            ->each(function (CommissionCharge $charge) use (&$charged) {
                try {
                    app(ChargeCommission::class)->handle($charge);
                    $charged++;
                } catch (\InvalidArgumentException $e) {
                    // Sin tarjeta guardada u otra condición previa — ya
                    // queda registrado en el estado de la comisión, no
                    // hace falta interrumpir el resto del lote por un
                    // solo negocio.
                    Log::warning("[Marketplace] Comisión {$charge->id} (negocio {$charge->business_id}) no se pudo cobrar: {$e->getMessage()}");
                }
            });

        return $charged;
    }
}
