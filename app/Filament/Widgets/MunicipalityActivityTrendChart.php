<?php

namespace App\Filament\Widgets;

use App\Domain\Analytics\Models\AnalyticsEvent;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MunicipalityActivityTrendChart extends ChartWidget
{
    protected ?string $heading = 'Tendencia de actividad';

    protected ?string $description = 'Evolución diaria de vistas y contactos por WhatsApp en toda la plataforma durante los últimos 7 días.';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 1,
    ];

    protected ?string $maxHeight = '220px';

    protected static ?int $sort = 3;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = collect(range(6, 0))
            ->map(fn (int $daysAgo) => now()->subDays($daysAgo)->startOfDay());

        $events = DB::table('analytics_events')
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('SUM(CASE WHEN type IN (?, ?) THEN 1 ELSE 0 END) as views_count', [
                AnalyticsEvent::VITRINA_VIEW,
                AnalyticsEvent::PRODUCTO_VIEW,
            ])
            ->selectRaw('SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as whatsapp_clicks_count', [
                AnalyticsEvent::WHATSAPP_CLICK,
            ])
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('day')
            ->get()
            ->keyBy(fn ($row) => $row->day);

        $labels = [];
        $views = [];
        $whatsappClicks = [];

        foreach ($days as $day) {
            $key = $day->toDateString();
            $labels[] = $day->translatedFormat('D');
            $views[] = (int) ($events[$key]->views_count ?? 0);
            $whatsappClicks[] = (int) ($events[$key]->whatsapp_clicks_count ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Vistas',
                    'data' => $views,
                    'borderColor' => 'rgb(227, 52, 47)',
                    'backgroundColor' => 'rgba(227, 52, 47, 0.15)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => 'Contactos por WhatsApp',
                    'data' => $whatsappClicks,
                    'borderColor' => 'rgb(245, 158, 11)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
