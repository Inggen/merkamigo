<?php

namespace App\Domain\Billing\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Suscripción de un negocio a un plan (4.1 del TODO): periodos de prueba,
 * gracia, suspensión, cancelación y reactivación.
 *
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $current_period_starts_at
 * @property Carbon|null $current_period_ends_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $grace_ends_at
 */
class Subscription extends Model
{
    public const PRUEBA = 'prueba';

    public const ACTIVA = 'activa';

    public const EN_GRACIA = 'en_gracia';

    public const SUSPENDIDA = 'suspendida';

    public const CANCELADA = 'cancelada';

    protected $fillable = [
        'business_id',
        'plan_id',
        'status',
        'trial_ends_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'cancelled_at',
        'grace_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'grace_ends_at' => 'datetime',
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

    public function isUsable(): bool
    {
        return in_array($this->status, [self::PRUEBA, self::ACTIVA, self::EN_GRACIA], true);
    }
}
