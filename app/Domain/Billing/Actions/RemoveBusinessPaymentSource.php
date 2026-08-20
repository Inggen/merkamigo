<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Quita la tarjeta guardada de un negocio (4.2 del TODO): el negocio
 * vuelve a depender del pago manual de un clic para renovar. No se llama
 * a Wompi para "borrar" la fuente de pago — Wompi no expone ese endpoint,
 * simplemente se deja de usar para futuros cobros.
 */
class RemoveBusinessPaymentSource
{
    public function handle(Business $business, User $actor): void
    {
        $business->update([
            'wompi_payment_source_id' => null,
            'card_brand' => null,
            'card_last_four' => null,
            'auto_renew_enabled' => false,
        ]);

        app(RecordAuditLog::class)->handle($actor, 'business.payment_source_removed', $business, [
            'business_id' => $business->id,
        ]);
    }
}
