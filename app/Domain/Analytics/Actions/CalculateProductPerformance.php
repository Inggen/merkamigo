<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Support\Carbon;

/**
 * Desglose de vistas y clics a WhatsApp por producto (4.5 del TODO):
 * `CalculateReadableMetrics` solo mira el negocio completo, esto agrupa
 * los mismos eventos por `subject_id` para responder "¿cuáles de mis
 * productos se ven más?". Mismo periodo de 7 días, calculado al vuelo
 * sobre `analytics_events` — sin tabla precalculada, igual criterio que
 * el resto de Analítica.
 */
class CalculateProductPerformance
{
    private const DAYS = 7;

    /**
     * @return array<int, array{product: Product, views: int, whatsapp_clicks: int, last_viewed_at: Carbon|null}>
     */
    public function handle(Business $business): array
    {
        $products = $business->products()->where('status', 'publicado')->get();

        if ($products->isEmpty()) {
            return [];
        }

        $productMorphClass = (new Product)->getMorphClass();
        $periodStart = now()->subDays(self::DAYS - 1)->startOfDay();

        $views = AnalyticsEvent::query()
            ->where('business_id', $business->id)
            ->where('type', AnalyticsEvent::PRODUCTO_VIEW)
            ->where('subject_type', $productMorphClass)
            ->whereIn('subject_id', $products->pluck('id'))
            ->where('created_at', '>=', $periodStart)
            ->selectRaw('subject_id, COUNT(*) as total')
            ->groupBy('subject_id')
            ->pluck('total', 'subject_id');

        $whatsappClicks = AnalyticsEvent::query()
            ->where('business_id', $business->id)
            ->where('type', AnalyticsEvent::WHATSAPP_CLICK)
            ->where('subject_type', $productMorphClass)
            ->whereIn('subject_id', $products->pluck('id'))
            ->where('created_at', '>=', $periodStart)
            ->selectRaw('subject_id, COUNT(*) as total')
            ->groupBy('subject_id')
            ->pluck('total', 'subject_id');

        $lastViewedAt = AnalyticsEvent::query()
            ->where('business_id', $business->id)
            ->where('type', AnalyticsEvent::PRODUCTO_VIEW)
            ->where('subject_type', $productMorphClass)
            ->whereIn('subject_id', $products->pluck('id'))
            ->selectRaw('subject_id, MAX(created_at) as last_viewed_at')
            ->groupBy('subject_id')
            ->pluck('last_viewed_at', 'subject_id');

        $rows = $products
            ->map(fn (Product $product) => [
                'product' => $product,
                'views' => (int) ($views[$product->id] ?? 0),
                'whatsapp_clicks' => (int) ($whatsappClicks[$product->id] ?? 0),
                'last_viewed_at' => filled($lastViewedAt[$product->id] ?? null)
                    ? Carbon::parse($lastViewedAt[$product->id])
                    : null,
            ])
            ->sortByDesc('views')
            ->values();

        return $rows->all();
    }
}
