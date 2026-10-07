<?php

namespace App\Domain\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Renglón de equipo cotizado en una reserva — mismo criterio de
 * "snapshot" que `EventReservationDish`.
 */
class EventReservationEquipment extends Model
{
    protected $fillable = [
        'event_reservation_id', 'event_business_equipment_id', 'name_snapshot', 'fee_cents_snapshot', 'quantity',
    ];

    protected function casts(): array
    {
        return [
            'fee_cents_snapshot' => 'integer',
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
     * @return BelongsTo<EventBusinessEquipment, $this>
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(EventBusinessEquipment::class, 'event_business_equipment_id');
    }
}
