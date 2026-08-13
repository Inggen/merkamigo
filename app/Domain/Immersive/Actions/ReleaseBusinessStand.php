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
     * override, el estado se decide solo: tuvo un slot alguna vez
     * (ocupado ahora o recordado en `previous_slot_id`) → `pausado`
     * (recuperable); nunca tuvo uno → `sin_configurar`.
     *
     * Bug real corregido (2026-08-12, reportado por un usuario): antes
     * `$hadSlot` solo miraba `stand_slot_id` actual. Si `handle()` se
     * llama dos veces seguidas sin publicar en el medio (ej.
     * `AssignBusinessToStand` ya había limpiado `stand_slot_id` al
     * marcar `sin_cupo`/`reubicacion_requerida`, y luego el negocio se
     * guarda de nuevo sin estar publicado), la segunda llamada veía
     * `stand_slot_id` en null y "olvidaba" que el negocio sí tuvo un
     * espacio antes — degradaba `pausado` a `sin_configurar` sin razón,
     * dejando el stand invisible para el emprendedor sin ningún aviso
     * claro de qué pasó. Ahora también cuenta `previous_slot_id`, que es
     * justo el campo pensado para recordar esto entre llamadas.
     */
    public function handle(Business $business, ?string $status = null): StandAssignment
    {
        $assignment = StandAssignment::firstOrCreate(
            ['business_id' => $business->id],
            ['status' => 'sin_configurar'],
        );

        $hadSlot = filled($assignment->stand_slot_id);
        $everHadSlot = $hadSlot || filled($assignment->previous_slot_id);

        if ($hadSlot) {
            $slot = StandSlot::find($assignment->stand_slot_id);

            if ($slot && $slot->status === 'ocupada') {
                $slot->update(['status' => 'disponible']);
            }
        }

        $assignment->update([
            'status' => $status ?? ($everHadSlot ? 'pausado' : 'sin_configurar'),
            'previous_slot_id' => $assignment->stand_slot_id ?? $assignment->previous_slot_id,
            'stand_slot_id' => null,
            'immersive_plaza_id' => null,
        ]);

        return $assignment->fresh();
    }
}
