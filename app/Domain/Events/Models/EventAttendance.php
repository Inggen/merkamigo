<?php

namespace App\Domain\Events\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Reserva de CUPO/asistencia a un evento público (pedido explícito del
 * usuario, 2026-10-08): distinta de `EventReservation`, que reserva el
 * ESPACIO del negocio para un evento privado propio con cotizador. Esta
 * reserva un lugar PARA asistir a un evento público ya existente (ej.
 * "Taller de cerámica"), limitada por `PublicEvent::capacity`. Si el
 * evento es gratuito, la reserva queda `confirmada` de inmediato; si es
 * de pago, pasa por el mismo patrón Wompi que el resto del módulo.
 *
 * `checkin_token_hash`/`checkin_token_encrypted`: mismo patrón que
 * `LoyaltyRedemption` — nunca se guarda el código en claro de forma
 * irreversible (hash para buscar), pero sí de forma reversible
 * (`encrypted`) para poder volver a mostrar el mismo QR si el asistente
 * reabre la página de su entrada.
 *
 * @property Carbon|null $expires_at
 * @property Carbon|null $checked_in_at
 */
class EventAttendance extends Model
{
    protected $fillable = [
        'public_event_id', 'business_id', 'customer_user_id', 'attendee_name', 'attendee_email',
        'attendee_phone', 'quantity', 'unit_price_cents', 'total_cents', 'status', 'expires_at',
        'checkin_token_hash', 'checkin_token_encrypted', 'checked_in_at', 'checked_in_by_user_id',
        'idempotency_key',
    ];

    protected $hidden = ['checkin_token_encrypted'];

    public const PENDIENTE_PAGO = 'pendiente_pago';

    public const CONFIRMADA = 'confirmada';

    public const PAGO_FALLIDO = 'pago_fallido';

    public const VENCIDA = 'vencida';

    public const CANCELADA = 'cancelada';

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'total_cents' => 'integer',
            'expires_at' => 'datetime',
            'checkin_token_encrypted' => 'encrypted',
            'checked_in_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PublicEvent, $this>
     */
    public function publicEvent(): BelongsTo
    {
        return $this->belongsTo(PublicEvent::class);
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
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by_user_id');
    }

    /**
     * @return HasMany<EventAttendancePaymentAttempt, $this>
     */
    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(EventAttendancePaymentAttempt::class);
    }

    public function isFree(): bool
    {
        return $this->total_cents === 0;
    }

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }
}
