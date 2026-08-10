<?php

namespace App\Filament\Resources\PlazaLegendEntries;

use App\Domain\Immersive\Models\PlazaLegendEntry;
use App\Filament\Resources\PlazaLegendEntries\Pages\EditPlazaLegendEntry;
use App\Filament\Resources\PlazaLegendEntries\Pages\ListPlazaLegendEntries;
use App\Filament\Resources\PlazaLegendEntries\Schemas\PlazaLegendEntryForm;
use App\Filament\Resources\PlazaLegendEntries\Tables\PlazaLegendEntriesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * IMM-013 del TODO inmersivo (redefinido): un color detectado en la imagen
 * de leyenda de una plaza, pendiente de mapear a un objeto del catálogo.
 * Las entradas solo las crea la acción "Detectar leyenda" de la plaza — no
 * hay alta manual, por eso este recurso no tiene página de creación.
 */
class PlazaLegendEntryResource extends Resource
{
    protected static ?string $model = PlazaLegendEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static UnitEnum|string|null $navigationGroup = 'Experiencias inmersivas';

    protected static ?string $modelLabel = 'color de leyenda';

    protected static ?string $pluralModelLabel = 'leyenda de colores';

    protected static ?string $navigationLabel = 'Leyenda de colores';

    public static function form(Schema $schema): Schema
    {
        return PlazaLegendEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlazaLegendEntriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlazaLegendEntries::route('/'),
            'edit' => EditPlazaLegendEntry::route('/{record}/edit'),
        ];
    }

    private static function canManage(): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['admin', 'superadmin']) ?? false;
    }

    public static function canViewAny(): bool
    {
        return self::canManage();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return self::canManage();
    }

    public static function canDeleteAny(): bool
    {
        return self::canManage();
    }

    public static function canDelete(Model $record): bool
    {
        return self::canManage();
    }
}
