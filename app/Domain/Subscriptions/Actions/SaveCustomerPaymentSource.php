<?php

namespace App\Domain\Subscriptions\Actions;

use App\Domain\Businesses\Models\Business;
use App\Support\Wompi\BusinessWompiClient;
use InvalidArgumentException;

/**
 * Tokeniza la tarjeta de un CLIENTE contra la cuenta Wompi del NEGOCIO
 * (Fase 8.2 del TODO social) — mismo patrón que
 * `Billing\SaveBusinessPaymentSource`, pero aquí todavía no existe una
 * `CustomerSubscription` a la cual asociar la fuente de pago (el cliente
 * apenas la está guardando); `SubscribeToProduct` la usa una vez
 * confirmada como `AVAILABLE`.
 */
class SaveCustomerPaymentSource
{
    /**
     * @return array<string, mixed>
     */
    public function handle(
        Business $business,
        string $cardToken,
        string $customerEmail,
        string $acceptanceToken,
        string $acceptPersonalAuthToken,
    ): array {
        if (! $business->hasWompiConnected()) {
            throw new InvalidArgumentException('Este negocio todavía no activó pagos en línea.');
        }

        $client = new BusinessWompiClient($business->wompiCredential);

        $data = $client->createPaymentSource([
            'type' => 'CARD',
            'token' => $cardToken,
            'customer_email' => $customerEmail,
            'acceptance_token' => $acceptanceToken,
            'accept_personal_auth' => $acceptPersonalAuthToken,
        ]);

        if (! is_array($data) || blank($data['id'] ?? null)) {
            throw new InvalidArgumentException('Wompi no pudo guardar la tarjeta. Intenta de nuevo.');
        }

        return $data;
    }
}
