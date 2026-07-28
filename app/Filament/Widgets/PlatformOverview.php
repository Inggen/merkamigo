<?php

namespace App\Filament\Widgets;

use App\Domain\Businesses\Models\Business;
use App\Domain\Moderation\Models\Report;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard operativo (1.9 del TODO): cifras que un moderador/administrador
 * necesita ver de un vistazo al entrar al panel.
 */
class PlatformOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Emprendedores registrados', User::query()->where('experience', 'emprendedor')->count()),
            Stat::make('Vitrinas publicadas', Business::query()->where('status', 'publicado')->count()),
            Stat::make('Pendientes de revisión', Business::query()->where('status', 'pendiente_revision')->count()),
            Stat::make('Negocios suspendidos', Business::query()->where('status', 'suspendido')->count()),
            Stat::make('Reportes pendientes', Report::query()->where('status', Report::PENDIENTE)->count())
                ->color(fn () => Report::query()->where('status', Report::PENDIENTE)->exists() ? 'danger' : 'success'),
        ];
    }
}
