<?php

namespace App\Domain\Billing\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Pago procesado por Wompi (4.2 del TODO). Nunca almacena datos de
 * tarjeta — el checkout es 100% hospedado por Wompi; aquí solo se guarda
 * referencia, monto, estado e id de transacción para conciliación.
 *
 * @property Carbon|null $paid_at
 */
class Payment extends Model
{
    public const PENDIENTE = 'pendiente';

    public const APROBADO = 'aprobado';

    public const RECHAZADO = 'rechazado';

    public const EN_PROCESO = 'en_proceso';

    public const REEMBOLSADO = 'reembolsado';

    protected $fillable = [
        'business_id',
        'plan_id',
        'billing_product_id',
        'reference',
        'wompi_transaction_id',
        'amount_cents',
        'currency',
        'status',
        'coupon_code',
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
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsTo<BillingProduct, $this>
     */
    public function billingProduct(): BelongsTo
    {
        return $this->belongsTo(BillingProduct::class);
    }
}
