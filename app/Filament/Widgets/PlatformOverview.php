<?php

namespace App\Filament\Widgets;

use App\Domain\Businesses\Models\Business;
use App\Domain\Moderation\Models\Report;
use App\Domain\Needs\Models\Need;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard operativo (1.9 del TODO): cifras que un moderador/administrador
 * necesita ver de un vistazo al entrar al panel.
 */
class PlatformOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Plataforma';

    protected ?string $description = 'Resumen general de la operación.';

    protected int|array|null $columns = ['default' => 2, 'md' => 3, 'xl' => 6];

    protected function getStats(): array
    {
        $totalNeeds = Need::query()->count();
        $activeNeeds = Need::query()
            ->whereIn('status', [Need::PUBLICADA, Need::RECIBIENDO_OFERTAS])
            ->whereNull('suspended_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();

        $pendingReportsCount = Report::query()->where('status', Report::PENDIENTE)->count();
        $suspendedCount = Business::query()->where('status', 'suspendido')->count();

        return [
            Stat::make('Emprendedores registrados', User::query()->where('experience', 'emprendedor')->count())
                ->icon(Heroicon::OutlinedUsers)
                ->color('primary'),
            Stat::make('Vitrinas publicadas', Business::query()->where('status', 'publicado')->count())
                ->icon(Heroicon::OutlinedBuildingStorefront)
                ->color('success'),
            Stat::make('Pendientes de revisión', Business::query()->where('status', 'pendiente_revision')->count())
                ->icon(Heroicon::OutlinedClock)
                ->color('warning'),
            Stat::make('Negocios suspendidos', $suspendedCount)
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color($suspendedCount > 0 ? 'danger' : 'gray'),
            Stat::make('Oportunidades totales', $totalNeeds)
                ->description("Activas: {$activeNeeds}")
                ->icon(Heroicon::OutlinedLightBulb)
                ->color('info'),
            Stat::make('Reportes pendientes', $pendingReportsCount)
                ->icon(Heroicon::OutlinedFlag)
                ->color($pendingReportsCount > 0 ? 'danger' : 'success'),
        ];
    }
}
