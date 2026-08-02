<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Needs\Models\Offer;
use Illuminate\Support\Collection;

/**
 * Tiempo hasta primera propuesta y hasta conexión (2.3 del TODO), medidos
 * sobre las propias propuestas del negocio: cuánto tarda en responder a una
 * solicitud publicada y cuánto tarda esa respuesta en generar un contacto
 * por WhatsApp (medido igual que `NeedsController::whatsapp`, vía
 * `analytics_events` con `subject` apuntando a la propuesta).
 */
class CalculateNeedFunnelMetrics
{
    /**
     * @return array{
     *     has_enough_data: bool,
     *     median_hours_to_first_offer: float|null,
     *     median_hours_to_connection: float|null,
     * }
     */
    public function handle(Business $business): array
    {
        $offers = Offer::query()
            ->where('business_id', $business->id)
            ->whereNotIn('status', [Offer::RETIRADA])
            ->with('need')
            ->get();

        $hoursToFirstOffer = $offers
            ->filter(fn (Offer $offer) => $offer->need !== null)
            ->map(fn (Offer $offer) => $offer->need->created_at->diffInMinutes($offer->created_at) / 60)
            ->values();

        $offerMorphClass = (new Offer)->getMorphClass();

        $firstWhatsAppClickByOffer = AnalyticsEvent::query()
            ->where('type', AnalyticsEvent::WHATSAPP_CLICK)
            ->where('subject_type', $offerMorphClass)
            ->whereIn('subject_id', $offers->pluck('id'))
            ->orderBy('created_at')
            ->get()
            ->unique('subject_id')
            ->keyBy('subject_id');

        $hoursToConnection = $offers
            ->map(function (Offer $offer) use ($firstWhatsAppClickByOffer) {
                $click = $firstWhatsAppClickByOffer->get($offer->id);

                return $click ? $offer->created_at->diffInMinutes($click->created_at) / 60 : null;
            })
            ->filter(fn (?float $hours) => $hours !== null)
            ->values();

        return [
            'has_enough_data' => $hoursToFirstOffer->isNotEmpty() || $hoursToConnection->isNotEmpty(),
            'median_hours_to_first_offer' => $this->median($hoursToFirstOffer),
            'median_hours_to_connection' => $this->median($hoursToConnection),
        ];
    }

    /**
     * @param  Collection<int, float>  $values
     */
    private function median(Collection $values): ?float
    {
        if ($values->isEmpty()) {
            return null;
        }

        $sorted = $values->sort()->values();
        $count = $sorted->count();
        $middle = intdiv($count, 2);

        return $count % 2 === 0
            ? ($sorted[$middle - 1] + $sorted[$middle]) / 2
            : $sorted[$middle];
    }
}
