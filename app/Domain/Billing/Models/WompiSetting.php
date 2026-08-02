<?php

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuración de Wompi editable desde el admin (4.2 del TODO): guarda
 * las credenciales de sandbox y de producción a la vez, con
 * `active_env` decidiendo cuál usa `WompiClient` en cada momento —
 * cambiar de ambiente no requiere tocar variables de entorno ni
 * desplegar de nuevo.
 *
 * Es un singleton: siempre hay una sola fila (`current()` la crea si no
 * existe). Mientras el admin no la haya configurado todavía, cada
 * credencial cae de vuelta a `config('services.wompi.*')` (que a su vez
 * lee del `.env`) — así el pago sigue funcionando exactamente igual que
 * antes de que existiera esta pantalla.
 */
class WompiSetting extends Model
{
    protected $table = 'wompi_settings';

    protected $fillable = [
        'active_env',
        'sandbox_public_key',
        'sandbox_private_key',
        'sandbox_integrity_secret',
        'sandbox_events_secret',
        'production_public_key',
        'production_private_key',
        'production_integrity_secret',
        'production_events_secret',
    ];

    protected $hidden = [
        'sandbox_private_key',
        'sandbox_integrity_secret',
        'sandbox_events_secret',
        'production_private_key',
        'production_integrity_secret',
        'production_events_secret',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'active_env' => config('services.wompi.env', 'sandbox'),
        ]);
    }

    public function isProduction(): bool
    {
        return $this->active_env === 'production';
    }

    public function publicKey(): ?string
    {
        $value = $this->isProduction() ? $this->production_public_key : $this->sandbox_public_key;

        return $value ?: config('services.wompi.public_key');
    }

    public function privateKey(): ?string
    {
        $value = $this->isProduction() ? $this->production_private_key : $this->sandbox_private_key;

        return $value ?: config('services.wompi.private_key');
    }

    public function integritySecret(): ?string
    {
        $value = $this->isProduction() ? $this->production_integrity_secret : $this->sandbox_integrity_secret;

        return $value ?: config('services.wompi.integrity_secret');
    }

    public function eventsSecret(): ?string
    {
        $value = $this->isProduction() ? $this->production_events_secret : $this->sandbox_events_secret;

        return $value ?: config('services.wompi.events_secret');
    }

    public function checkoutUrl(): string
    {
        return config('services.wompi.checkout_url', 'https://checkout.wompi.co/p/');
    }

    public function apiUrl(): string
    {
        return $this->isProduction()
            ? 'https://production.wompi.co/v1'
            : 'https://sandbox.wompi.co/v1';
    }
}
