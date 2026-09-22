<?php

namespace App\Domain\Subscriptions\Models;

use App\Domain\Storefronts\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan de suscripción de un producto de un negocio a SUS clientes (Fase
 * 8.1 del TODO social) — un plan por producto en este alcance.
 */
class SubscriptionPlan extends Model
{
    public const SEMANAL = 'semanal';

    public const MENSUAL = 'mensual';

    public const TRIMESTRAL = 'trimestral';

    public const ANUAL = 'anual';

    protected $fillable = [
        'product_id',
        'frequency',
        'trial_days',
        'benefits',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<CustomerSubscription, $this>
     */
    public function customerSubscriptions(): HasMany
    {
        return $this->hasMany(CustomerSubscription::class);
    }

    public function periodLength(): \DateInterval
    {
        return match ($this->frequency) {
            self::SEMANAL => new \DateInterval('P1W'),
            self::TRIMESTRAL => new \DateInterval('P3M'),
            self::ANUAL => new \DateInterval('P1Y'),
            default => new \DateInterval('P1M'),
        };
    }
}
