<?php

namespace App\Domain\Immersive\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Immersive\Models\StandAssignment;
use App\Domain\Immersive\Models\StandSlot;

/**
 * IMM-023 del TODO inmersivo: "al desactivar una vitrina, su asignación
 * pasa a inactiva y el slot queda disponible". Guarda el slot en
 * `previous_slot_id` para que `AssignBusinessToStand` intente recuperarlo
 * primero si el negocio vuelve a publicarse.
 */
class ReleaseBusinessStand
{
    /**
     * `$status` es un override explícito (lo usa `AssignBusinessToStand`
     * cuando el negocio nunca llegó a tener plantilla/slot). Sin
     * override, el estado se decide solo: tenía un slot ocupado →
     * `pausado` (recuperable); nunca tuvo uno → `sin_configurar`.
     */
    public function handle(Business $business, ?string $status = null): StandAssignment
    {
        $assignment = StandAssignment::firstOrCreate(
            ['business_id' => $business->id],
            ['status' => 'sin_configurar'],
        );

        $hadSlot = filled($assignment->stand_slot_id);

        if ($hadSlot) {
            $slot = StandSlot::find($assignment->stand_slot_id);

            if ($slot && $slot->status === 'ocupada') {
                $slot->update(['status' => 'disponible']);
            }
        }

        $assignment->update([
            'status' => $status ?? ($hadSlot ? 'pausado' : 'sin_configurar'),
            'previous_slot_id' => $assignment->stand_slot_id ?? $assignment->previous_slot_id,
            'stand_slot_id' => null,
            'immersive_plaza_id' => null,
        ]);

        return $assignment->fresh();
    }
}
