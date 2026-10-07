<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyReceiptClaim;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Solicitud excepcional con recibo (TODO_Merkapuntos.md, F2.7): el cliente
 * olvidó mostrar su QR al pagar y sube evidencia para revisión manual. No
 * acredita nada por sí sola — un empleado la aprueba o rechaza aparte.
 */
class SubmitLoyaltyReceiptClaim
{
    /**
     * @throws LoyaltyActionException
     */
    public function handle(Business $business, User $customer, UploadedFile $receipt, ?string $description = null): LoyaltyReceiptClaim
    {
        $enrollment = $business->loyaltyEnrollment;

        if (! $enrollment || ! $enrollment->isActive()) {
            throw new LoyaltyActionException('Este negocio no tiene Merkamigo Premia activo.');
        }

        $maxKb = (int) config('loyalty.receipt_claims.max_upload_kb', 5120);

        if ($receipt->getSize() > $maxKb * 1024) {
            throw new LoyaltyActionException("El archivo supera el límite de {$maxKb} KB.");
        }

        // Nunca en el disco `public` — un recibo puede traer datos del
        // cliente (ver reglas de producto sobre disco privado).
        $path = $receipt->store('loyalty-receipts/'.$business->id, 'private');

        $claim = LoyaltyReceiptClaim::create([
            'business_id' => $business->id,
            'customer_user_id' => $customer->id,
            'receipt_path' => $path,
            'description' => $description,
            'status' => LoyaltyReceiptClaim::PENDIENTE,
        ]);

        app(RecordAuditLog::class)->handle($customer, 'loyalty.receipt_claim.submitted', $claim, [
            'business_id' => $business->id,
        ]);

        return $claim;
    }
}
