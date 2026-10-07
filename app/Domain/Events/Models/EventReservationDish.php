<?php

namespace App\Domain\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Renglón de platos cotizados en una reserva. Guarda nombre y precio
 * "snapshot" al momento de cotizar para que un cambio posterior en el
 * menú del negocio no altere reservas ya cotizadas o pagadas.
 */
class EventReservationDish extends Model
{
    protected $fillable = [
        'event_reservation_id', 'event_dish_id', 'name_snapshot', 'unit_price_cents_snapshot', 'quantity',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_cents_snapshot' => 'integer',
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<EventReservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(EventReservation::class, 'event_reservation_id');
    }

    /**
     * @return BelongsTo<EventDish, $this>
     */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(EventDish::class, 'event_dish_id');
    }
}
