<?php

namespace App\Domain\Needs\Actions;

use App\Domain\Needs\Models\Need;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Cancelación de una solicitud por su dueño (2.1/2.3 del TODO). Distinta
 * de `CloseNeed`: no registra un resultado, y aplica también a borradores
 * que el comprador ya no quiere continuar.
 */
class CancelNeed
{
    public function handle(Need $need, User $actor): Need
    {
        $need->update([
            'status' => Need::CANCELADA,
            'closed_at' => now(),
        ]);

        app(RecordAuditLog::class)->handle($actor, 'need.cancelled', $need);

        return $need->fresh();
    }
}
