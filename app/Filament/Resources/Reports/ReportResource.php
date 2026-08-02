<?php

namespace App\Filament\Resources\Reports;

use App\Domain\Moderation\Models\Report;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Resources\Reports\Tables\ReportsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * 1.3/1.4/1.9 del TODO: reportes de contenido — solo listado con acciones
 * de resolver/descartar, sin crear ni editar manualmente.
 */
class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static UnitEnum|string|null $navigationGroup = 'Moderación';

    protected static ?string $modelLabel = 'reporte';

    protected static ?string $pluralModelLabel = 'reportes';

    protected static ?string $navigationLabel = 'Reportes';

    public static function table(Table $table): Table
    {
        return ReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['moderator', 'admin', 'superadmin']) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['superadmin']) ?? false;
    }
}
