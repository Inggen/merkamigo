<?php

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Models\CommissionCharge;
use App\Domain\Marketplace\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Suma un pedido pagado a la comisión "abierta" del negocio (el período
 * que todavía no se ha cobrado) — crea una si no existe ninguna abierta.
 * `ChargeCommission` es quien la cierra y la cobra.
 */
class AccrueCommission
{
    public function handle(Order $order): CommissionCharge
    {
        return DB::transaction(function () use ($order) {
            $charge = CommissionCharge::query()
                ->where('business_id', $order->business_id)
                ->where('status', CommissionCharge::ABIERTA)
                ->lockForUpdate()
                ->first();

            if (! $charge) {
                $charge = CommissionCharge::create([
                    'business_id' => $order->business_id,
                    'period_start' => now()->toDateString(),
                    'period_end' => now()->toDateString(),
                    'status' => CommissionCharge::ABIERTA,
                ]);
            }

            $charge->update([
                'period_end' => now()->toDateString(),
                'orders_count' => $charge->orders_count + 1,
                'gross_amount_cents' => $charge->gross_amount_cents + $order->amount_cents,
                'commission_cents' => $charge->commission_cents + $order->commission_cents,
            ]);

            $order->update(['commission_charge_id' => $charge->id]);

            return $charge->fresh();
        });
    }
}
