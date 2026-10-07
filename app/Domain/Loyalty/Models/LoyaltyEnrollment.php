<?php

namespace App\Domain\Loyalty\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adhesión voluntaria de un negocio a Merkamigo Premia (TODO_Merkapuntos.md,
 * F3.1). Un negocio sin adhesión `activa` no puede operar el escáner ni
 * publicar premios — la pertenencia al programa es explícita, nunca
 * implícita por tener un plan pagado o estar publicado.
 */
class LoyaltyEnrollment extends Model
{
    public const PENDIENTE = 'pendiente';

    public const ACTIVA = 'activa';

    public const SUSPENDIDA = 'suspendida';

    public const RETIRADA = 'retirada';

    protected $fillable = [
        'business_id',
        'status',
        'consent_version',
        'consented_at',
        'responsible_user_id',
        'budget_cents',
    ];

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'budget_cents' => 'integer',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVA;
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
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
