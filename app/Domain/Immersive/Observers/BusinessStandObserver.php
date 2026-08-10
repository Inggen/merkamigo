<?php

namespace App\Domain\Immersive\Observers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Immersive\Actions\AssignBusinessToStand;
use App\Domain\Immersive\Actions\ReleaseBusinessStand;

/**
 * IMM-022/IMM-023 del TODO inmersivo: mantiene `StandAssignment` en
 * sincronía con el ciclo de vida del negocio, sin que `Business` sepa
 * nada de la experiencia inmersiva (el acoplamiento es de un solo
 * sentido — este observer vive en el dominio Immersive, no en
 * Businesses). Se re-evalúa en cada `saved()` (no solo cuando cambia
 * `status`) para que sea auto-reparable: si alguna vez queda
 * desincronizado, el siguiente guardado del negocio lo corrige solo.
 */
class BusinessStandObserver
{
    public function saved(Business $business): void
    {
        $this->sync($business);
    }

    public function deleting(Business $business): void
    {
        app(ReleaseBusinessStand::class)->handle($business);
    }

    private function sync(Business $business): void
    {
        if ($business->isPublished()) {
            app(AssignBusinessToStand::class)->handle($business);

            return;
        }

        app(ReleaseBusinessStand::class)->handle($business);
    }
}
