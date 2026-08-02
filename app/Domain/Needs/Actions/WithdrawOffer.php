<?php

namespace App\Domain\Needs\Actions;

use App\Domain\Needs\Models\Offer;
use App\Domain\Needs\Notifications\OfferWithdrawn;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Un negocio retira su propuesta (2.2 del TODO). El comprador deja de
 * verla entre las propuestas activas, pero el registro se conserva
 * (trazabilidad) en vez de borrarse.
 */
class WithdrawOffer
{
    public function handle(Offer $offer, User $actor): Offer
    {
        $offer->update([
            'status' => Offer::RETIRADA,
            'withdrawn_at' => now(),
        ]);

        app(RecordAuditLog::class)->handle($actor, 'offer.withdrawn', $offer);

        $offer->need->user->notify(new OfferWithdrawn($offer));

        return $offer->fresh();
    }
}
