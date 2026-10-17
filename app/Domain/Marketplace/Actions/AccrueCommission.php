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
            // Defensa adicional a la del lock en `ApplyApprovedOrder` (su
            // único llamador hoy): si este pedido ya se acumuló antes, no
            // volver a sumarlo. Cierra el hallazgo #1 de la auditoría
            // (condición de carrera webhook/retorno duplicando la
            // comisión) también para cualquier llamador futuro que no
            // pase por ese lock.
            if ($order->commission_charge_id) {
                return CommissionCharge::findOrFail($order->commission_charge_id);
            }

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
