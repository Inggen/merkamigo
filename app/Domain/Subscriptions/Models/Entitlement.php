<?php

namespace App\Domain\Subscriptions\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Acceso de un cliente a un producto digital (Fase 9 del TODO social) —
 * no confundir con `App\Domain\Billing\Models\BusinessEntitlement`
 * (capacidad desbloqueada para un NEGOCIO, dominio distinto). `null` en
 * `expires_at` es una compra única y permanente; con fecha, se refresca
 * en cada cobro de periodo aprobado de la suscripción que lo otorgó.
 */
class Entitlement extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'business_id',
        'order_id',
        'customer_subscription_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<CustomerSubscription, $this>
     */
    public function customerSubscription(): BelongsTo
    {
        return $this->belongsTo(CustomerSubscription::class);
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
