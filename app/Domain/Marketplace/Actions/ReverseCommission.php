<?php

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Models\CommissionCharge;
use App\Domain\Marketplace\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Resta un pedido reembolsado/anulado de la comisión que ya se le había
 * sumado (PR2 de TODO_VENTAS_RENTABILIDAD.md, decisión del usuario
 * 2026-10-09: "restar del cargo abierto si aún no se cobró"). Si el
 * cargo ya no está `abierta` (ya se está cobrando o ya se cobró), no se
 * ajusta automáticamente — se deja constancia en el log para revisión
 * manual, decisión explícita para no construir todavía un mecanismo de
 * crédito/ajuste sobre comisiones ya cobradas.
 */
class ReverseCommission
{
    public function handle(Order $order): void
    {
        if (! $order->commission_charge_id) {
            return;
        }

        DB::transaction(function () use ($order) {
            $charge = CommissionCharge::whereKey($order->commission_charge_id)
                ->lockForUpdate()
                ->first();

            if (! $charge) {
                return;
            }

            if ($charge->status !== CommissionCharge::ABIERTA) {
                Log::warning("[Marketplace] Pedido {$order->id} reembolsado pero su comisión (cargo {$charge->id}, estado {$charge->status}) ya no está abierta — no se ajustó automáticamente, requiere revisión manual.");

                return;
            }

            $charge->update([
                'orders_count' => max(0, $charge->orders_count - 1),
                'gross_amount_cents' => max(0, $charge->gross_amount_cents - $order->amount_cents),
                'commission_cents' => max(0, $charge->commission_cents - $order->commission_cents),
            ]);

            $order->update(['commission_charge_id' => null]);
        });
    }
}
