<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Notifications\PaymentFailed;

/**
 * Aplica el efecto de un pago según su estado real en Wompi (4.2 del
 * TODO) — llamado tanto desde el retorno del checkout como desde el
 * webhook, de forma idempotente (no vuelve a aplicar un pago ya
 * `aprobado`/`rechazado`).
 */
class ApplyApprovedPayment
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function handle(Payment $payment, string $wompiStatus, ?string $wompiTransactionId, array $rawResponse = []): Payment
    {
        if (in_array($payment->status, [Payment::APROBADO, Payment::RECHAZADO], true)) {
            return $payment;
        }

        $status = match ($wompiStatus) {
            'APPROVED' => Payment::APROBADO,
            'DECLINED', 'ERROR', 'VOIDED' => Payment::RECHAZADO,
            default => Payment::EN_PROCESO,
        };

        $payment->update([
            'status' => $status,
            'wompi_transaction_id' => $wompiTransactionId,
            'raw_response' => $rawResponse,
            'paid_at' => $status === Payment::APROBADO ? now() : null,
        ]);

        if ($status === Payment::APROBADO) {
            $this->applyEffect($payment);
        }

        if ($status === Payment::RECHAZADO) {
            $payment->business->members->each(fn ($member) => $member->notify(new PaymentFailed($payment)));
        }

        return $payment->fresh();
    }

    private function applyEffect(Payment $payment): void
    {
        if ($payment->plan_id) {
            $plan = Plan::findOrFail($payment->plan_id);
            $periodEnd = match ($plan->billing_period) {
                Plan::MENSUAL => now()->addMonth(),
                Plan::ANUAL => now()->addYear(),
                default => null,
            };

            app(SubscribeToPlan::class)->handle($payment->business, $plan, null, $periodEnd);
        }

        if ($payment->billing_product_id) {
            app(ApplyBillingProductPurchase::class)->handle($payment);
        }
    }
}
