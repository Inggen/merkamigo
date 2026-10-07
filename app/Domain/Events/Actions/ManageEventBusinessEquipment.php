<?php

namespace App\Domain\Events\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventBusinessEquipment;
use App\Domain\Events\Models\EventEquipmentType;

/**
 * Selección de equipos de un negocio para sus eventos (Fase 2 · pestaña
 * Configuración → Equipos): del catálogo global (sonido/micrófono/
 * televisor y lo que agregue el administrador) o propios ("Otro equipo").
 * Nunca modifica `EventEquipmentType` (catálogo global).
 */
class ManageEventBusinessEquipment
{
    public function selectGlobal(Business $business, EventEquipmentType $type, ?int $feeCents = null): EventBusinessEquipment
    {
        if ($feeCents !== null && $feeCents < 0) {
            throw new EventActionException('El cargo no puede ser negativo.');
        }

        return $business->eventEquipment()->updateOrCreate(
            ['event_equipment_type_id' => $type->id],
            ['fee_cents' => $feeCents, 'is_active' => true],
        );
    }

    public function unselectGlobal(Business $business, EventEquipmentType $type): void
    {
        $business->eventEquipment()->where('event_equipment_type_id', $type->id)->delete();
    }

    /**
     * @param  array{custom_name: string, description?: ?string, fee_cents?: ?int}  $data
     */
    public function addCustom(Business $business, array $data): EventBusinessEquipment
    {
        if (($data['fee_cents'] ?? null) !== null && $data['fee_cents'] < 0) {
            throw new EventActionException('El cargo no puede ser negativo.');
        }

        return $business->eventEquipment()->create([...$data, 'is_active' => true]);
    }

    public function remove(EventBusinessEquipment $equipment): void
    {
        $equipment->delete();
    }
}
