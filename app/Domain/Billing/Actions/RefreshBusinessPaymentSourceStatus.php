<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Businesses\Models\Business;
use App\Support\Wompi\WompiClient;
use InvalidArgumentException;

/**
 * Sondea el estado real de la fuente de pago guardada mientras Wompi
 * corre la validación 3D Secure (`PENDING` con pasos `BROWSER_INFO` /
 * `FINGERPRINT` / `CHALLENGE` / `AUTHENTICATION`, ver 4.2 del TODO). El
 * frontend llama esto cada pocos segundos hasta que el estado deja de ser
 * `PENDING`.
 */
class RefreshBusinessPaymentSourceStatus
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Business $business): array
    {
        if (blank($business->wompi_payment_source_id)) {
            throw new InvalidArgumentException('Este negocio no tiene una fuente de pago guardada.');
        }

        $data = app(WompiClient::class)->fetchPaymentSource($business->wompi_payment_source_id);

        if (! is_array($data)) {
            throw new InvalidArgumentException('No pudimos consultar el estado de la tarjeta con Wompi.');
        }

        $status = $data['status'] ?? null;

        if ($status === 'AVAILABLE') {
            $business->update(['auto_renew_enabled' => true]);
        } elseif (in_array($status, ['DECLINED', 'ERROR'], true)) {
            $business->update([
                'wompi_payment_source_id' => null,
                'card_brand' => null,
                'card_last_four' => null,
                'auto_renew_enabled' => false,
            ]);
        }

        return $data;
    }
}
