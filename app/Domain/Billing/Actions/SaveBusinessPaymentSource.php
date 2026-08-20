<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use App\Support\Wompi\WompiClient;
use InvalidArgumentException;

/**
 * Guarda la tarjeta de un negocio para la renovación mensual automática
 * (4.2 del TODO). El número de tarjeta y el CVV nunca llegan aquí — el
 * navegador los tokeniza directamente contra Wompi con la llave pública
 * (ver `PaymentSourceController`); esta acción solo recibe el token ya
 * generado y crea la "fuente de pago" en Wompi con la llave privada.
 *
 * La fuente de pago puede quedar `PENDING` mientras Wompi corre la
 * validación 3D Secure (ver `RefreshBusinessPaymentSourceStatus`) — recién
 * en `AVAILABLE` se activa `auto_renew_enabled`.
 */
class SaveBusinessPaymentSource
{
    /**
     * @return array<string, mixed>
     */
    public function handle(
        Business $business,
        string $cardToken,
        string $cardBrand,
        string $cardLastFour,
        string $customerEmail,
        string $acceptanceToken,
        string $acceptPersonalAuthToken,
        User $actor,
    ): array {
        $data = app(WompiClient::class)->createPaymentSource([
            'type' => 'CARD',
            'token' => $cardToken,
            'customer_email' => $customerEmail,
            'acceptance_token' => $acceptanceToken,
            'accept_personal_auth' => $acceptPersonalAuthToken,
        ]);

        if (! is_array($data) || blank($data['id'] ?? null)) {
            throw new InvalidArgumentException('Wompi no pudo guardar la tarjeta. Intenta de nuevo.');
        }

        $business->update([
            'wompi_payment_source_id' => (string) $data['id'],
            'card_brand' => $cardBrand,
            'card_last_four' => $cardLastFour,
            'auto_renew_enabled' => ($data['status'] ?? null) === 'AVAILABLE',
        ]);

        app(RecordAuditLog::class)->handle($actor, 'business.payment_source_saved', $business, [
            'business_id' => $business->id,
            'wompi_payment_source_id' => $data['id'],
            'status' => $data['status'] ?? null,
        ]);

        return $data;
    }
}
