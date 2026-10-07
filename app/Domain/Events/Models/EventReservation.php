<?php

namespace App\Domain\Events\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Reserva privada de un espacio de evento (Fase 3/4). El prospecto puede
 * no tener cuenta ("puedes explorar y reservar sin registrarte" en la
 * referencia de diseño) — los datos de contacto van aparte de
 * `customer_user_id`, que es opcional.
 *
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property Carbon|null $expires_at
 * @property Carbon $terms_accepted_at
 * @property array{pricing_mode: string, hourly_rate_cents: int|null} $pricing_snapshot
 */
class EventReservation extends Model
{
    protected $fillable = [
        'business_id', 'event_space_id', 'public_event_id', 'customer_user_id',
        'prospect_name', 'prospect_email', 'prospect_phone', 'starts_at', 'ends_at',
        'duration_hours', 'party_size', 'pricing_snapshot', 'dishes_total_cents',
        'space_total_cents', 'equipment_total_cents', 'total_cents', 'status',
        'expires_at', 'terms_accepted_at', 'cancellation_reason', 'idempotency_key',
    ];

    public const PENDIENTE_PAGO = 'pendiente_pago';

    public const CONFIRMADA = 'confirmada';

    public const PAGO_FALLIDO = 'pago_fallido';

    public const VENCIDA = 'vencida';

    public const CANCELADA = 'cancelada';

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'duration_hours' => 'integer',
            'party_size' => 'integer',
            'pricing_snapshot' => 'array',
            'dishes_total_cents' => 'integer',
            'space_total_cents' => 'integer',
            'equipment_total_cents' => 'integer',
            'total_cents' => 'integer',
            'expires_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
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
     * @return BelongsTo<EventSpace, $this>
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(EventSpace::class, 'event_space_id');
    }

    /**
     * @return BelongsTo<PublicEvent, $this>
     */
    public function publicEvent(): BelongsTo
    {
        return $this->belongsTo(PublicEvent::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    /**
     * @return HasMany<EventReservationDish, $this>
     */
    public function dishes(): HasMany
    {
        return $this->hasMany(EventReservationDish::class);
    }

    /**
     * @return HasMany<EventReservationEquipment, $this>
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(EventReservationEquipment::class);
    }

    /**
     * @return HasMany<EventPaymentAttempt, $this>
     */
    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(EventPaymentAttempt::class);
    }
}
