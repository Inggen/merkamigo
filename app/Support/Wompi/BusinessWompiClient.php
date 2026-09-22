<?php

namespace App\Support\Wompi;

use App\Domain\Marketplace\Models\BusinessWompiCredential;
use Illuminate\Support\Facades\Http;

/**
 * Integración con Wompi usando las credenciales DEL NEGOCIO, nunca las de
 * Merkamigo (`App\Support\Wompi\WompiClient`) — a propósito una clase
 * separada, aunque duplique parte de la lógica, para no arriesgar ni un
 * carácter del cliente que ya sostiene el cobro de suscripciones en
 * producción. Mismas fórmulas de firma que Wompi documenta, ver
 * `WompiClient` para el comentario completo.
 */
class BusinessWompiClient
{
    public function __construct(private readonly BusinessWompiCredential $credential) {}

    public function publicKey(): string
    {
        return $this->credential->public_key;
    }

    public function checkoutUrl(): string
    {
        return $this->credential->checkoutUrl();
    }

    public function integritySignature(string $reference, int $amountInCents, string $currency = 'COP'): string
    {
        return hash('sha256', $reference.$amountInCents.$currency.$this->credential->integrity_secret);
    }

    public function privateKey(): string
    {
        return $this->credential->private_key;
    }

    /**
     * Tarjeta del CLIENTE tokenizada contra la cuenta Wompi del NEGOCIO
     * (Fase 8 del TODO social: suscripciones cliente → negocio) — mismo
     * patrón que `WompiClient::createPaymentSource()`, pero cada negocio
     * guarda las tarjetas de SUS propios suscriptores en SU propia cuenta,
     * nunca en la de Merkamigo.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function createPaymentSource(array $payload): ?array
    {
        $response = Http::withToken($this->credential->private_key)
            ->post($this->credential->apiUrl().'/payment_sources', $payload);

        return $response->json('data');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchPaymentSource(string $paymentSourceId): ?array
    {
        $response = Http::withToken($this->credential->private_key)
            ->get($this->credential->apiUrl()."/payment_sources/{$paymentSourceId}");

        return $response->json('data');
    }

    /**
     * Cobra la renovación periódica de una suscripción contra la tarjeta
     * ya guardada del cliente, sin que esté presente (ver
     * `App\Domain\Subscriptions\Actions\ChargeSubscriptionPeriod`).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function chargePaymentSource(array $payload): ?array
    {
        $response = Http::withToken($this->credential->private_key)
            ->post($this->credential->apiUrl().'/transactions', $payload);

        return $response->json();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchTransaction(string $transactionId): ?array
    {
        $response = Http::withToken($this->credential->public_key)
            ->get($this->credential->apiUrl()."/transactions/{$transactionId}");

        if (! $response->successful()) {
            return null;
        }

        return $response->json('data');
    }

    /**
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
        $concatenated .= $this->credential->events_secret;

        return hash_equals(hash('sha256', $concatenated), $checksum);
    }

    /**
     * Confirma que la llave pública realmente existe en Wompi antes de
     * guardarla (`ConnectBusinessWompi`) — evita guardar una llave mal
     * copiada que solo fallaría hasta el primer cliente que intente pagar.
     *
     * @return array<string, mixed>|null
     */
    public function fetchMerchant(): ?array
    {
        $response = Http::withToken($this->credential->public_key)
            ->get($this->credential->apiUrl()."/merchants/{$this->credential->public_key}");

        if (! $response->successful()) {
            return null;
        }

        return $response->json('data');
    }
}
