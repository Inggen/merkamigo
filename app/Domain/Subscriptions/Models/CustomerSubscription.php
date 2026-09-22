<?php

namespace App\Domain\Subscriptions\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Suscripción de un CLIENTE a un producto de un negocio (Fase 8.2 del
 * TODO social). No confundir con `App\Domain\Billing\Models\Subscription`
 * (negocio → Merkamigo, intocable) — el cobro periódico aquí va directo a
 * la cuenta Wompi del negocio, igual que `Marketplace\Order`.
 */
class CustomerSubscription extends Model
{
    public const PRUEBA = 'prueba';

    public const ACTIVA = 'activa';

    public const PAUSADA = 'pausada';

    public const CANCELADA = 'cancelada';

    public const VENCIDA = 'vencida';

    protected $fillable = [
        'business_id',
        'product_id',
        'subscription_plan_id',
        'buyer_user_id',
        'status',
        'wompi_payment_source_id',
        'card_brand',
        'card_last_four',
        'trial_ends_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<SubscriptionPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isUsable(): bool
    {
        return in_array($this->status, [self::PRUEBA, self::ACTIVA], true);
    }

    public function hasSavedCard(): bool
    {
        return filled($this->wompi_payment_source_id);
    }
}
