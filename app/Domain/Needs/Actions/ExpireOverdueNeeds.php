<?php

namespace App\Domain\Needs\Actions;

use App\Domain\Needs\Models\Need;

/**
 * Cierra por vencimiento las necesidades publicadas cuya fecha de
 * expiración ya pasó (2.1/2.3 del TODO: "fecha de expiración" / "cerrar
 * por vencimiento"). La ejecuta el comando programado
 * `needs:expire-overdue`.
 */
class ExpireOverdueNeeds
{
    public function handle(): int
    {
        return Need::query()
            ->whereIn('status', [Need::PUBLICADA, Need::RECIBIENDO_OFERTAS, Need::SELECCIONADA])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => Need::VENCIDA,
                'closed_at' => now(),
            ]);
    }
}
