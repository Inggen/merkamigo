<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Convierte los eventos crudos de `analytics_events` en cifras que un
 * emprendedor sin conocimientos analíticos pueda entender (1.8 del TODO).
 * Las "métricas diarias" se calculan agrupando por día en el momento de la
 * consulta en vez de mantener una tabla `daily_business_metrics`
 * precalculada: al volumen del piloto (dos municipios) es igual de rápido y
 * siempre queda conciliable con los eventos reales, sin necesitar un job en
 * cola todavía (ver docs/architecture/decisiones.md).
 */
class CalculateReadableMetrics
{
    private const DAYS = 7;

    /**
     * @return array{
     *     summary: string,
     *     total_views: int,
     *     total_whatsapp_clicks: int,
     *     previous_total_views: int,
     *     previous_total_whatsapp_clicks: int,
     *     views_by_day: array<int, array{label: string, count: int}>,
     *     whatsapp_clicks_by_day: array<int, array{label: string, count: int}>,
     * }
     */
    public function handle(Business $business): array
    {
        $periodStart = now()->subDays(self::DAYS - 1)->startOfDay();
        $periodEnd = now()->endOfDay();
        $previousStart = now()->subDays((self::DAYS * 2) - 1)->startOfDay();
        $previousEnd = now()->subDays(self::DAYS)->endOfDay();

        $viewTypes = [AnalyticsEvent::VITRINA_VIEW, AnalyticsEvent::PRODUCTO_VIEW];

        $viewsByDay = $this->countsByDay($business, $viewTypes, $periodStart, $periodEnd);
        $whatsappByDay = $this->countsByDay($business, [AnalyticsEvent::WHATSAPP_CLICK], $periodStart, $periodEnd);

        $totalViews = array_sum($viewsByDay);
        $totalWhatsapp = array_sum($whatsappByDay);

        $previousTotalViews = $this->totalBetween($business, $viewTypes, $previousStart, $previousEnd);
        $previousTotalWhatsapp = $this->totalBetween($business, [AnalyticsEvent::WHATSAPP_CLICK], $previousStart, $previousEnd);

        return [
            'summary' => $this->summary($totalViews, $totalWhatsapp),
            'total_views' => $totalViews,
            'total_whatsapp_clicks' => $totalWhatsapp,
            'previous_total_views' => $previousTotalViews,
            'previous_total_whatsapp_clicks' => $previousTotalWhatsapp,
            'views_by_day' => $this->toChartSeries($viewsByDay),
            'whatsapp_clicks_by_day' => $this->toChartSeries($whatsappByDay),
        ];
    }

    /**
     * @param  array<int, string>  $types
     * @return array<string, int> fecha (Y-m-d) => cantidad, para los últimos self::DAYS días.
     */
    private function countsByDay(Business $business, array $types, CarbonInterface $start, CarbonInterface $end): array
    {
        $rows = AnalyticsEvent::query()
            ->where('business_id', $business->id)
            ->whereIn('type', $types)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = [];
        $cursor = $start;

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $days[$key] = (int) ($rows[$key] ?? 0);
            $cursor = $cursor->addDay();
        }

        return $days;
    }

    /**
     * @param  array<int, string>  $types
     */
    private function totalBetween(Business $business, array $types, CarbonInterface $start, CarbonInterface $end): int
    {
        return AnalyticsEvent::query()
            ->where('business_id', $business->id)
            ->whereIn('type', $types)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    /**
     * @param  array<string, int>  $countsByDay
     * @return array<int, array{label: string, count: int}>
     */
    private function toChartSeries(array $countsByDay): array
    {
        return (new Collection($countsByDay))
            ->map(fn (int $count, string $day) => [
                'label' => CarbonImmutable::parse($day)->translatedFormat('D'),
                'count' => $count,
            ])
            ->values()
            ->all();
    }

    private function summary(int $totalViews, int $totalWhatsapp): string
    {
        if ($totalViews === 0 && $totalWhatsapp === 0) {
            return __('Todavía no hay visitas ni contactos esta semana. Comparte tu enlace o QR para empezar a recibirlos.');
        }

        return __('Esta semana :views personas vieron tu negocio y :clicks te escribieron por WhatsApp.', [
            'views' => $totalViews,
            'clicks' => $totalWhatsapp,
        ]);
    }
}
