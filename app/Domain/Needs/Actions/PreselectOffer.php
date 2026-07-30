<?php

namespace App\Domain\Needs\Actions;

use App\Domain\Needs\Models\Offer;
use App\Models\User;

/**
 * El comprador marca una propuesta como preseleccionada mientras compara
 * (2.2/2.3 del TODO), sin cerrar todavía la solicitud.
 */
class PreselectOffer
{
    public function handle(Offer $offer, User $actor): Offer
    {
        if ($offer->isActive() && $offer->status !== Offer::ACEPTADA) {
            $offer->update(['status' => Offer::PRESELECCIONADA]);
        }

        return $offer->fresh();
    }
}
