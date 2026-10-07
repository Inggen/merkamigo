<?php

namespace App\Domain\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Intento de pago Wompi de una reserva de asistencia a un evento público
 * — mismo patrón de idempotencia por `reference` única que
 * `EventPaymentAttempt` (reservas de espacio) y `Order`.
 */
class EventAttendancePaymentAttempt extends Model
{
    protected $fillable = [
        'event_attendance_id', 'reference', 'amount_cents', 'currency', 'status',
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
     * @return BelongsTo<EventAttendance, $this>
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(EventAttendance::class, 'event_attendance_id');
    }
}
