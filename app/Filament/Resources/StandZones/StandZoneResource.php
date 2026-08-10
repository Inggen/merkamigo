<?php

namespace App\Filament\Resources\StandZones;

use App\Domain\Immersive\Models\StandZone;
use App\Filament\Resources\StandZones\Pages\CreateStandZone;
use App\Filament\Resources\StandZones\Pages\EditStandZone;
use App\Filament\Resources\StandZones\Pages\ListStandZones;
use App\Filament\Resources\StandZones\Schemas\StandZoneForm;
use App\Filament\Resources\StandZones\Tables\StandZonesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * IMM-013 del TODO inmersivo: polígono permitido dentro de una plaza donde
 * se pueden reservar stands.
 */
class StandZoneResource extends Resource
{
    protected static ?string $model = StandZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected static UnitEnum|string|null $navigationGroup = 'Experiencias inmersivas';

    protected static ?string $modelLabel = 'zona de stands';

    protected static ?string $pluralModelLabel = 'zonas de stands';

    protected static ?string $navigationLabel = 'Zonas de stands';

    public static function form(Schema $schema): Schema
    {
        return StandZoneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StandZonesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStandZones::route('/'),
            'create' => CreateStandZone::route('/create'),
            'edit' => EditStandZone::route('/{record}/edit'),
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
        return self::canManage();
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
