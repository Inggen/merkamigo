<?php

namespace App\Support\Wompi;

use App\Domain\Billing\Models\WompiSetting;
use Illuminate\Support\Facades\Http;

/**
 * Integración con Wompi (4.2 del TODO): firma de integridad del checkout,
 * verificación de transacciones y verificación de la firma de eventos de
 * webhook. Sigue la documentación oficial de Wompi Colombia — nunca se
 * confía solo en la redirección del navegador, siempre se verifica contra
 * la API.
 *
 * Las credenciales salen de `WompiSetting::current()` (editable desde el
 * admin, sandbox y producción a la vez), con `config('services.wompi.*')`
 * como respaldo mientras nadie las haya configurado ahí todavía.
 */
class WompiClient
{
    public function publicKey(): string
    {
        return (string) WompiSetting::current()->publicKey();
    }

    public function checkoutUrl(): string
    {
        return WompiSetting::current()->checkoutUrl();
    }

    /**
     * Firma de integridad del checkout: SHA256(reference + amountInCents +
     * currency + integrity_secret), tal como documenta Wompi.
     */
    public function integritySignature(string $reference, int $amountInCents, string $currency = 'COP'): string
    {
        return hash('sha256', $reference.$amountInCents.$currency.WompiSetting::current()->integritySecret());
    }

    /**
     * Consulta el estado real de una transacción contra la API de Wompi —
     * nunca se confía en el `?id=` de la redirección por sí solo.
     *
     * @return array<string, mixed>|null
     */
    public function fetchTransaction(string $transactionId): ?array
    {
        $settings = WompiSetting::current();

        $response = Http::withToken($settings->publicKey())
            ->get($settings->apiUrl()."/transactions/{$transactionId}");

        if (! $response->successful()) {
            return null;
        }

        return $response->json('data');
    }

    /**
     * Verifica la firma de un evento de webhook: SHA256(valores de
     * `signature.properties` concatenados en ese orden + timestamp +
     * events_secret), comparado contra `signature.checksum`.
     *
     * @param  array<string, mixed>  $event
     */
    public function verifyEventSignature(array $event): bool
    {
        $properties = $event['signature']['properties'] ?? null;
        $checksum = $event['signature']['checksum'] ?? null;
        $timestamp = $event['timestamp'] ?? null;

        if (! is_array($properties) || ! is_string($checksum) || $timestamp === null) {
            return false;
        }

        $concatenated = '';

        foreach ($properties as $property) {
            $concatenated .= data_get($event, $property);
        }

        $concatenated .= $timestamp;
        $concatenated .= WompiSetting::current()->eventsSecret();

        return hash_equals(hash('sha256', $concatenated), $checksum);
    }

    /**
     * Endpoint de anulación de una transacción aprobada (política de
     * reembolso, 4.2 del TODO) — usa la llave privada del ambiente activo.
     */
    public function voidTransactionUrl(string $wompiTransactionId): string
    {
        return WompiSetting::current()->apiUrl()."/transactions/{$wompiTransactionId}/void";
    }

    public function privateKey(): string
    {
        return (string) WompiSetting::current()->privateKey();
    }

    /**
     * Datos del comercio, incluidos los `acceptance_token` (aceptación de
     * términos y de tratamiento de datos personales) que Wompi exige para
     * crear una fuente de pago — se piden con la llave pública, sin
     * autenticación privada.
     *
     * @return array<string, mixed>|null
     */
    public function fetchMerchant(): ?array
    {
        $response = Http::withToken($this->publicKey())
            ->get(WompiSetting::current()->apiUrl()."/merchants/{$this->publicKey()}");

        if (! $response->successful()) {
            return null;
        }

        return $response->json('data');
    }

    /**
     * Crea una fuente de pago (tarjeta tokenizada en el navegador, débito
     * automático — 4.2 del TODO) contra Wompi. Requiere la llave privada,
     * por eso se hace desde el servidor y no desde el navegador.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function createPaymentSource(array $payload): ?array
    {
        $response = Http::withToken($this->privateKey())
            ->post(WompiSetting::current()->apiUrl().'/payment_sources', $payload);

        return $response->json('data');
    }

    /**
     * Consulta el estado de una fuente de pago — usado para sondear la
     * validación 3D Secure hasta que quede `AVAILABLE`, `DECLINED` o
     * `ERROR` (ver `RefreshBusinessPaymentSourceStatus`).
     *
     * @return array<string, mixed>|null
     */
    public function fetchPaymentSource(string $paymentSourceId): ?array
    {
        $response = Http::withToken($this->privateKey())
            ->get(WompiSetting::current()->apiUrl()."/payment_sources/{$paymentSourceId}");

        return $response->json('data');
    }

    /**
     * Cobra una transacción contra una fuente de pago guardada, sin el
     * cliente presente (renovación mensual automática — ver
     * `ChargeSubscriptionRenewal`).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function chargePaymentSource(array $payload): ?array
    {
        $response = Http::withToken($this->privateKey())
            ->post(WompiSetting::current()->apiUrl().'/transactions', $payload);

        return $response->json();
    }
}
