<?php

namespace App\Filament\Widgets;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Discovery\Models\Municipality;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Panel admin por municipio (4.5 del TODO): negocios, vistas y clics a
 * WhatsApp agrupados por municipio principal — junto a `PlatformOverview`
 * para que el equipo vea de un vistazo dónde está la actividad. Cuenta
 * solo el municipio principal de cada negocio (no los adicionales de
 * 0.2.2), igual que el resto de reportes agregados por municipio.
 */
class MunicipalityOverview extends TableWidget
{
    protected static ?string $heading = 'Actividad por municipio';

    public static function canView(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->query())
            ->columns([
                TextColumn::make('name')
                    ->label('Municipio'),
                TextColumn::make('businesses_count')
                    ->label('Negocios publicados')
                    ->sortable(),
                TextColumn::make('views_count')
                    ->label('Vistas (7 días)')
                    ->sortable(),
                TextColumn::make('whatsapp_clicks_count')
                    ->label('Contactos por WhatsApp (7 días)')
                    ->sortable(),
            ])
            ->defaultSort('businesses_count', 'desc')
            ->paginated(false);
    }

    /**
     * @return Builder<Municipality>
     */
    private function query(): Builder
    {
        $periodStart = now()->subDays(6)->startOfDay();

        return Municipality::query()
            ->withCount(['businesses' => fn (Builder $q) => $q->where('status', 'publicado')])
            ->withCount(['businesses as views_count' => fn (Builder $q) => $q
                ->join('analytics_events', 'analytics_events.business_id', '=', 'businesses.id')
                ->whereIn('analytics_events.type', [AnalyticsEvent::VITRINA_VIEW, AnalyticsEvent::PRODUCTO_VIEW])
                ->where('analytics_events.created_at', '>=', $periodStart)])
            ->withCount(['businesses as whatsapp_clicks_count' => fn (Builder $q) => $q
                ->join('analytics_events', 'analytics_events.business_id', '=', 'businesses.id')
                ->where('analytics_events.type', AnalyticsEvent::WHATSAPP_CLICK)
                ->where('analytics_events.created_at', '>=', $periodStart)]);
    }
}
