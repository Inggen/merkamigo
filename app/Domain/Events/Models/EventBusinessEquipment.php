<?php

namespace App\Domain\Events\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Equipo que un negocio ofrece para sus eventos: un tipo del catálogo
 * global, o uno propio ("Otro") identificado solo por `custom_name`.
 */
class EventBusinessEquipment extends Model
{
    protected $fillable = [
        'business_id', 'event_equipment_type_id', 'custom_name', 'description',
        'fee_cents', 'quantity_available', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fee_cents' => 'integer',
            'quantity_available' => 'integer',
            'is_active' => 'boolean',
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
     * @return BelongsTo<EventEquipmentType, $this>
     */
    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EventEquipmentType::class, 'event_equipment_type_id');
    }

    /**
     * Nombre a mostrar: el del catálogo global, o el propio si es "Otro".
     * Comprueba el FK (siempre fiable como nullable para PHPStan) antes de
     * tocar la relación, en vez de un `?->` sobre la relación misma.
     */
    public function displayName(): string
    {
        if (filled($this->custom_name)) {
            return $this->custom_name;
        }

        return $this->event_equipment_type_id ? $this->equipmentType->name : '';
    }
}
