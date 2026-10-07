<?php

namespace App\Domain\Loyalty\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Canje reservado por un cliente (TODO_Merkapuntos.md, F1.5/F1.6/F2.5).
 * `reward_snapshot` congela título/costo/condiciones al reservar: cambiar
 * el premio después nunca altera un canje ya en curso. `token_hash` es el
 * código que el negocio escanea para entregar — el token crudo solo existe
 * en la respuesta de `ReserveLoyaltyRedemption`, nunca se persiste en
 * claro.
 *
 * @property array{title: string, type: string, points_cost: int, full_cost_cents: int, terms: ?string} $reward_snapshot
 * @property Carbon $expires_at
 * @property Carbon|null $delivered_at
 */
class LoyaltyRedemption extends Model
{
    public const RESERVADO = 'reservado';

    public const ENTREGADO = 'entregado';

    public const CANCELADO = 'cancelado';

    public const EXPIRADO = 'expirado';

    protected $fillable = [
        'account_id',
        'reward_id',
        'reward_snapshot',
        'points_reserved',
        'cost_reserved_cents',
        'token_hash',
        'token_encrypted',
        'status',
        'delivered_by_user_id',
        'delivered_at',
        'cancelled_reason',
        'expires_at',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'reward_snapshot' => 'array',
            'points_reserved' => 'integer',
            'cost_reserved_cents' => 'integer',
            'token_encrypted' => 'encrypted',
            'delivered_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isReserved(): bool
    {
        return $this->status === self::RESERVADO;
    }

    public function isExpired(): bool
    {
        return $this->isReserved() && $this->expires_at->isPast();
    }

    /**
     * @return BelongsTo<LoyaltyAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(LoyaltyAccount::class, 'account_id');
    }

    /**
     * @return BelongsTo<LoyaltyReward, $this>
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(LoyaltyReward::class, 'reward_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by_user_id');
    }
}
