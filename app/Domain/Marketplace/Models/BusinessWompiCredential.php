<?php

namespace App\Domain\Marketplace\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Credenciales de Wompi DEL NEGOCIO (no de Merkamigo) — decisión de
 * arquitectura del 15 sep 2026: el cobro de un producto va directo a la
 * cuenta del negocio, Merkamigo nunca recauda dinero de terceros.
 */
class BusinessWompiCredential extends Model
{
    protected $fillable = [
        'business_id',
        'public_key',
        'private_key',
        'integrity_secret',
        'events_secret',
        'environment',
        'is_active',
        'connected_at',
    ];

    protected $hidden = [
        'private_key',
        'integrity_secret',
        'events_secret',
    ];

    protected function casts(): array
    {
        return [
            // Cifradas a nivel de aplicación: son credenciales de un
            // tercero, no propias — el estándar de protección es más
            // alto que el de `wompi_settings` (cuenta de Merkamigo).
            'private_key' => 'encrypted',
            'integrity_secret' => 'encrypted',
            'events_secret' => 'encrypted',
            'is_active' => 'boolean',
            'connected_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    public function apiUrl(): string
    {
        return $this->isProduction()
            ? 'https://production.wompi.co/v1'
            : 'https://sandbox.wompi.co/v1';
    }

    public function checkoutUrl(): string
    {
        return config('services.wompi.checkout_url', 'https://checkout.wompi.co/p/');
    }
}
