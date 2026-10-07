<?php

namespace App\Domain\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Intento de pago Wompi de una reserva (Fase 4) — mismo patrón de
 * idempotencia por `reference` única que `Order`/Merkapuntos.
 *
 * @property array<string, mixed>|null $raw_response
 */
class EventPaymentAttempt extends Model
{
    protected $fillable = [
        'event_reservation_id', 'reference', 'amount_cents', 'currency', 'status',
        'wompi_transaction_id', 'raw_response',
    ];

    public const PENDIENTE = 'pendiente';

    public const APROBADO = 'aprobado';

    public const RECHAZADO = 'rechazado';

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'raw_response' => 'array',
        ];
    }

    /**
     * @return BelongsTo<EventReservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(EventReservation::class, 'event_reservation_id');
    }
}
