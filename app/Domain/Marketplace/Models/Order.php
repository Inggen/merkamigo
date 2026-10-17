<?php

namespace App\Domain\Marketplace\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Models\ContentPromotion;
use App\Domain\Social\Models\LiveStream;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Subscriptions\Models\CustomerSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pedido de un producto. El pago va directo a la cuenta Wompi del
 * negocio (ver `BusinessWompiCredential`) — este registro es la
 * constancia de la venta, nunca mueve dinero por sí mismo.
 *
 * `$buyer` se anota explícitamente como nulable: desde PR3 de
 * TODO_VENTAS_RENTABILIDAD.md, `buyer_user_id` es una FK nullable (un
 * invitado no tiene cuenta) — sin esto, Larastan infiere la relación
 * `BelongsTo` como "nunca null" solo a partir del tipo declarado de
 * `buyer()`, sin mirar la nulabilidad real de la columna.
 *
 * @property User|null $buyer
 */
class Order extends Model
{
    public const PENDIENTE = 'pendiente';

    public const PAGADO = 'pagado';

    public const RECHAZADO = 'rechazado';

    public const CANCELADO = 'cancelado';

    /**
     * Pagado y luego revertido en Wompi (`VOIDED` después de `APPROVED`)
     * — distinto de `RECHAZADO` (nunca llegó a aprobarse). Ver
     * `ReverseCommission`: solo este estado dispara la reversa de la
     * comisión ya acumulada.
     */
    public const REEMBOLSADO = 'reembolsado';

    protected $fillable = [
        'business_id',
        'product_id',
        'content_promotion_id',
        'live_stream_id',
        'customer_subscription_id',
        'buyer_user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'quantity',
        'unit_price_cents',
        'amount_cents',
        'currency',
        'commission_cents',
        'commission_charge_id',
        'reference',
        'wompi_transaction_id',
        'status',
        'raw_response',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
            'paid_at' => 'datetime',
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

    public function contentPromotion(): BelongsTo
    {
        return $this->belongsTo(ContentPromotion::class);
    }

    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    /**
     * @return BelongsTo<CommissionCharge, $this>
     */
    public function commissionCharge(): BelongsTo
    {
        return $this->belongsTo(CommissionCharge::class);
    }

    /**
     * @return BelongsTo<CustomerSubscription, $this>
     */
    public function customerSubscription(): BelongsTo
    {
        return $this->belongsTo(CustomerSubscription::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAGADO;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::REEMBOLSADO;
    }

    /**
     * PR3 de TODO_VENTAS_RENTABILIDAD.md (P0.2): un pedido sin
     * `buyer_user_id` es de un invitado que pagó sin crear cuenta —
     * `guest_*` tiene sus datos de contacto en vez de una relación a
     * `users`.
     */
    public function isGuestOrder(): bool
    {
        return $this->buyer_user_id === null;
    }
}
