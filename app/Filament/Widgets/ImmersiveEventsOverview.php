<?php

namespace App\Filament\Widgets;

use App\Domain\Analytics\Models\ImmersiveEvent;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * IMM-043 (Fase 4 del TODO inmersivo): cifras de navegación de las plazas
 * inmersivas de los últimos 7 días — mismo criterio de ventana que
 * `MunicipalityActivityChart` (hermano de este widget, mismo dominio de
 * analítica).
 */
class ImmersiveEventsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $heading = 'Analítica inmersiva';

    protected ?string $description = 'Actividad de las plazas 3D en los últimos 7 días.';

    protected int|array|null $columns = ['default' => 2, 'md' => 3, 'xl' => 5];

    protected function getStats(): array
    {
        $days = $this->periodDays();
        $countsByType = $this->countsByTypeAndDay();

        $stat = function (string $type, string $label, Heroicon $icon, string $color) use ($days, $countsByType): Stat {
            $trend = array_map(fn (string $day) => $countsByType[$type][$day] ?? 0, $days);

            return Stat::make($label, array_sum($trend))
                ->icon($icon)
                ->color($color)
                ->chart(array_map(floatval(...), $trend))
                ->chartColor($color);
        };

        return [
            $stat(ImmersiveEvent::PLAZA_ENTRY, 'Entradas a plazas', Heroicon::OutlinedMapPin, 'primary'),
            $stat(ImmersiveEvent::SEARCH_PERFORMED, 'Búsquedas', Heroicon::OutlinedMagnifyingGlass, 'gray'),
            $stat(ImmersiveEvent::VITRINA_OPENED, 'Vitrinas abiertas', Heroicon::OutlinedBuildingStorefront, 'warning'),
            $stat(ImmersiveEvent::PRODUCT_VIEWED, 'Productos vistos', Heroicon::OutlinedEye, 'info'),
            $stat(ImmersiveEvent::WHATSAPP_CLICK, 'Clics a WhatsApp', Heroicon::OutlinedChatBubbleLeftRight, 'success'),
        ];
    }

    /**
     * @return list<string>
     */
    private function periodDays(): array
    {
        return array_map(
            fn (int $daysAgo) => now()->subDays($daysAgo)->format('Y-m-d'),
            range(6, 0),
        );
    }

    /**
     * Una sola consulta agrupada por tipo y día en vez de una consulta de
     * conteo por estadística (antes 5) — misma cantidad de datos, un solo
     * viaje a la base de datos, y ya trae lo necesario para la mini
     * tendencia de 7 días de cada tarjeta.
     *
     * @return array<string, array<string, int>>
     */
    private function countsByTypeAndDay(): array
    {
        $rows = DB::table('immersive_events')
            ->selectRaw('type, DATE(created_at) as day, COUNT(*) as aggregate')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('type', 'day')
            ->get();

        $byType = [];

        foreach ($rows as $row) {
            $byType[$row->type][$row->day] = (int) $row->aggregate;
        }

        return $byType;
    }
}
