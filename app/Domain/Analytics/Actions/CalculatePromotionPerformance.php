<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Models\ContentPromotion;
use App\Domain\Social\Models\LiveStream;
use App\Domain\Social\Models\Post;
use App\Domain\Social\Models\Story;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Support\Collection;

class CalculatePromotionPerformance
{
    /**
     * @return array{impressions: int, clicks: int, conversions: int, rows: Collection<int, array<string, mixed>>}
     */
    public function handle(Business $business, int $days = 7): array
    {
        $days = in_array($days, [7, 30, 90], true) ? $days : 7;
        $periodStart = now()->subDays($days - 1)->startOfDay();
        $promotions = $business->contentPromotions()
            ->with('promotable')
            ->where(fn ($query) => $query
                ->where('created_at', '>=', $periodStart)
                ->orWhere('ends_at', '>=', $periodStart))
            ->get();

        $counts = AnalyticsEvent::query()
            ->where('business_id', $business->id)
            ->where('subject_type', (new ContentPromotion)->getMorphClass())
            ->whereIn('subject_id', $promotions->pluck('id'))
            ->where('created_at', '>=', $periodStart)
            ->selectRaw('subject_id, type, COUNT(*) as total')
            ->groupBy('subject_id', 'type')
            ->get()
            ->groupBy('subject_id');

        $rows = $promotions->map(function (ContentPromotion $promotion) use ($counts) {
            $promotionCounts = $counts->get($promotion->id, collect())->pluck('total', 'type');

            return [
                'promotion' => $promotion,
                'content' => $this->contentLabel($promotion),
                'impressions' => (int) ($promotionCounts[AnalyticsEvent::PROMOTION_IMPRESSION] ?? 0),
                'clicks' => (int) ($promotionCounts[AnalyticsEvent::PROMOTION_CLICK] ?? 0),
                'conversions' => (int) ($promotionCounts[AnalyticsEvent::PROMOTION_CONVERSION] ?? 0),
            ];
        });

        return [
            'impressions' => $rows->sum('impressions'),
            'clicks' => $rows->sum('clicks'),
            'conversions' => $rows->sum('conversions'),
            'rows' => $rows,
        ];
    }

    private function contentLabel(ContentPromotion $promotion): string
    {
        $content = $promotion->promotable;

        return match (true) {
            $content instanceof Product => __('Producto: :name', ['name' => $content->name]),
            $content instanceof LiveStream => __('Live: :name', ['name' => $content->title]),
            $content instanceof Story => __('Estado: :name', ['name' => str($content->caption ?: __('Sin texto'))->limit(45)]),
            $content instanceof Post => __('Publicación: :name', ['name' => str($content->body ?: __('Sin texto'))->limit(45)]),
            default => __('Contenido eliminado'),
        };
    }
}
