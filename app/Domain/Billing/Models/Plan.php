<?php

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan de suscripción (4.1 del TODO): precios y límites configurables
 * desde administración, nunca codificados. `price_cents` nulo significa
 * gratuito (el plan "Gratis" sembrado por defecto).
 *
 * @property array<string, int|null>|null $limits
 * @property array<int, string>|null $features
 */
class Plan extends Model
{
    public const MENSUAL = 'mensual';

    public const ANUAL = 'anual';

    public const PAGO_UNICO = 'pago_unico';

    public const EMPRENDEDOR = 'emprendedor';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'price_cents',
        'billing_period',
        'limits',
        'features',
        'trial_days',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'limits' => 'array',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFree(): bool
    {
        return blank($this->price_cents);
    }

    /**
     * Límite configurado para una clave dada (ej. `max_products`), o null
     * si el plan no define un tope para esa clave (sin límite).
     */
    public function limit(string $key): ?int
    {
        return $this->limits[$key] ?? null;
    }
}
