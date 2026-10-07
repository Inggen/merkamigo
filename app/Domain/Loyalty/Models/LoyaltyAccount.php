<?php

namespace App\Domain\Loyalty\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cuenta de puntos de un cliente en un negocio (TODO_Merkapuntos.md: "Los
 * puntos de compras pertenecen al ámbito del negocio emisor. No sumar
 * distintos ámbitos como si fueran un saldo canjeable universal"). Única
 * por combinación (negocio, cliente) — ver índice único en la migración.
 *
 * El saldo nunca se guarda como columna: se reconstruye sumando
 * `loyalty_movements.points` (F1.2: "una única fuente de verdad para
 * saldo"). `availablePoints()` ya descuenta lo reservado en canjes
 * pendientes.
 */
class LoyaltyAccount extends Model
{
    public const ACTIVA = 'activa';

    public const SUSPENDIDA = 'suspendida';

    protected $fillable = [
        'business_id',
        'user_id',
        'status',
    ];

    public function availablePoints(): int
    {
        return (int) $this->movements()->sum('points');
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<LoyaltyMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(LoyaltyMovement::class, 'account_id');
    }

    /**
     * @return HasMany<LoyaltyRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(LoyaltyRedemption::class, 'account_id');
    }
}
