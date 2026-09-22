<?php

namespace App\Domain\Subscriptions\Actions;

use App\Domain\Businesses\Models\Business;
use App\Support\Wompi\BusinessWompiClient;
use InvalidArgumentException;

/**
 * Sondea el estado de una fuente de pago recién creada mientras Wompi
 * corre la validación 3D Secure — mismo motivo que
 * `Billing\RefreshBusinessPaymentSourceStatus`, aquí sin persistir nada
 * todavía (la fuente aún no pertenece a ninguna `CustomerSubscription`).
 */
class FetchCustomerPaymentSourceStatus
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Business $business, string $paymentSourceId): array
    {
        $client = new BusinessWompiClient($business->wompiCredential);

        $data = $client->fetchPaymentSource($paymentSourceId);

        if (! is_array($data)) {
            throw new InvalidArgumentException('No pudimos consultar el estado de la tarjeta con Wompi.');
        }

        return $data;
    }
}
