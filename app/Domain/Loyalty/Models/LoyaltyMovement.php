<?php

namespace App\Domain\Loyalty\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Libro de movimientos de puntos (TODO_Merkapuntos.md, F1.2): única fuente
 * de verdad del saldo, append-only — nunca se edita ni se borra una fila
 * existente, solo se agregan movimientos nuevos (incluidas las reversiones,
 * que referencian al movimiento original vía `metadata.reverses_movement_id`).
 */
class LoyaltyMovement extends Model
{
    public const ACUMULACION = 'acumulacion';

    public const RESERVA_CANJE = 'reserva_canje';

    public const CONSUMO_CANJE = 'consumo_canje';

    public const LIBERACION_CANJE = 'liberacion_canje';

    public const REVERSION = 'reversion';

    public const AJUSTE = 'ajuste';

    protected $fillable = [
        'account_id',
        'type',
        'points',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'actor_user_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<LoyaltyAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(LoyaltyAccount::class, 'account_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
