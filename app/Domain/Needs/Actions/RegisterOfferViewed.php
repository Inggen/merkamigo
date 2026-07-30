<?php

namespace App\Domain\Needs\Actions;

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Needs\Models\Offer;
use Illuminate\Http\Request;

/**
 * Registra que el comprador vio una propuesta (2.2 del TODO: "registrar
 * propuesta vista"), reutilizando la tabla genérica `analytics_events`
 * (1.8 del TODO) en vez de crear una tabla nueva solo para esto.
 */
class RegisterOfferViewed
{
    public function handle(Offer $offer, Request $request): void
    {
        if ($offer->viewed_at === null) {
            $offer->update(['viewed_at' => now()]);

            if ($offer->status === Offer::ENVIADA) {
                $offer->update(['status' => Offer::VISTA]);
            }
        }

        app(RegisterAnalyticsEvent::class)->handle($offer->business, AnalyticsEvent::OFERTA_VIEW, $offer, $request);
    }
}
