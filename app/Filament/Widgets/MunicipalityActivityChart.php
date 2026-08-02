<?php

namespace App\Filament\Widgets;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Discovery\Models\Municipality;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class MunicipalityActivityChart extends ChartWidget
{
    protected ?string $heading = 'Actividad por municipio';

    protected ?string $description = 'Comparativo de vistas y contactos por WhatsApp en los últimos 7 días.';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 1,
    ];

    protected ?string $maxHeight = '220px';

    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $periodStart = now()->subDays(6)->startOfDay();

        $municipalities = Municipality::query()
            ->withCount(['businesses as views_count' => fn (Builder $q) => $q
                ->join('analytics_events', 'analytics_events.business_id', '=', 'businesses.id')
                ->whereIn('analytics_events.type', [AnalyticsEvent::VITRINA_VIEW, AnalyticsEvent::PRODUCTO_VIEW])
                ->where('analytics_events.created_at', '>=', $periodStart)])
            ->withCount(['businesses as whatsapp_clicks_count' => fn (Builder $q) => $q
                ->join('analytics_events', 'analytics_events.business_id', '=', 'businesses.id')
                ->where('analytics_events.type', AnalyticsEvent::WHATSAPP_CLICK)
                ->where('analytics_events.created_at', '>=', $periodStart)])
            ->orderByDesc('views_count')
            ->orderByDesc('whatsapp_clicks_count')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Vistas',
                    'data' => $municipalities->pluck('views_count')->all(),
                    'backgroundColor' => 'rgba(227, 52, 47, 0.82)',
                    'borderColor' => 'rgb(227, 52, 47)',
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                ],
                [
                    'label' => 'Contactos por WhatsApp',
                    'data' => $municipalities->pluck('whatsapp_clicks_count')->all(),
                    'backgroundColor' => 'rgba(251, 191, 36, 0.8)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                ],
            ],
            'labels' => $municipalities->pluck('name')->all(),
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
