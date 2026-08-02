<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Payment;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use App\Support\Wompi\WompiClient;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * Reembolso de un pago aprobado (4.2 del TODO: "política de reembolso").
 * Solo disponible para superadmin vía Filament — la decisión de negocio
 * de cuándo reembolsar es humana, esto solo ejecuta la solicitud contra
 * Wompi y deja constancia auditada.
 */
class RefundPayment
{
    public function handle(Payment $payment, User $actor): Payment
    {
        if ($payment->status !== Payment::APROBADO) {
            throw new InvalidArgumentException('Solo se pueden reembolsar pagos aprobados.');
        }

        $wompi = app(WompiClient::class);

        Http::withToken($wompi->privateKey())
            ->post($wompi->voidTransactionUrl((string) $payment->wompi_transaction_id));

        $payment->update(['status' => Payment::REEMBOLSADO]);

        app(RecordAuditLog::class)->handle($actor, 'payment.refunded', $payment, [
            'business_id' => $payment->business_id,
        ]);

        return $payment->fresh();
    }
}
