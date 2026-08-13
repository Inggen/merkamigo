<?php

namespace App\Filament\Widgets;

use App\Domain\Analytics\Models\ImmersiveEvent;
use App\Domain\Immersive\Models\ImmersivePlaza;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * IMM-043 (Fase 4 del TODO inmersivo): comparativo de entradas y vitrinas
 * abiertas por plaza — mismo estilo visual y ventana de 7 días que
 * `MunicipalityActivityChart` (hermano de este widget).
 */
class ImmersivePlazaActivityChart extends ChartWidget
{
    protected ?string $heading = 'Actividad por plaza inmersiva';

    protected ?string $description = 'Entradas y vitrinas abiertas por plaza en los últimos 7 días.';

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

        $plazas = ImmersivePlaza::query()
            ->withCount(['immersiveEvents as entries_count' => fn (Builder $q) => $q
                ->where('type', ImmersiveEvent::PLAZA_ENTRY)
                ->where('created_at', '>=', $periodStart)])
            ->withCount(['immersiveEvents as vitrinas_opened_count' => fn (Builder $q) => $q
                ->where('type', ImmersiveEvent::VITRINA_OPENED)
                ->where('created_at', '>=', $periodStart)])
            ->orderByDesc('entries_count')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Entradas',
                    'data' => $plazas->pluck('entries_count')->all(),
                    'backgroundColor' => 'rgba(227, 52, 47, 0.82)',
                    'borderColor' => 'rgb(227, 52, 47)',
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                ],
                [
                    'label' => 'Vitrinas abiertas',
                    'data' => $plazas->pluck('vitrinas_opened_count')->all(),
                    'backgroundColor' => 'rgba(251, 191, 36, 0.8)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 1,
                    'borderRadius' => 8,
                ],
            ],
            'labels' => $plazas->pluck('name')->all(),
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
