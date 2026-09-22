<?php

namespace App\Domain\Marketplace\Models;

use App\Domain\Billing\Models\Payment;
use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Comisión de Merkamigo por las ventas de un período, cobrada aparte
 * contra la tarjeta ya guardada del negocio (decisión del usuario:
 * Merkamigo nunca recauda el pago del cliente, solo su propia comisión).
 */
class CommissionCharge extends Model
{
    public const ABIERTA = 'abierta';

    public const PENDIENTE_COBRO = 'pendiente_cobro';

    public const PAGADA = 'pagada';

    public const FALLIDA = 'fallida';

    protected $fillable = [
        'business_id',
        'period_start',
        'period_end',
        'orders_count',
        'gross_amount_cents',
        'commission_cents',
        'status',
        'payment_id',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
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
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
