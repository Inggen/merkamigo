<?php

namespace App\Domain\Loyalty\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compra presencial registrada por un empleado autorizado
 * (TODO_Merkapuntos.md, F1.3/F1.4). Guarda una copia (`policy_snapshot`) de
 * la política usada para calcular `points_awarded`, así que cambiar la
 * política del negocio después nunca reescribe compras pasadas.
 */
class LoyaltyPurchase extends Model
{
    public const PRESENCIAL = 'presencial';

    public const PEDIDO = 'pedido';

    public const REGISTRADA = 'registrada';

    public const REVERTIDA = 'revertida';

    protected $fillable = [
        'business_id',
        'customer_user_id',
        'eligible_amount_cents',
        'origin',
        'external_reference',
        'status',
        'policy_id',
        'policy_snapshot',
        'points_awarded',
        'employee_user_id',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'eligible_amount_cents' => 'integer',
            'points_awarded' => 'integer',
            'policy_snapshot' => 'array',
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
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    /**
     * @return BelongsTo<LoyaltyPolicy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(LoyaltyPolicy::class, 'policy_id');
    }
}
