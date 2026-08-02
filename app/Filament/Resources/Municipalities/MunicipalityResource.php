<?php

namespace App\Filament\Resources\Municipalities;

use App\Domain\Discovery\Models\Municipality;
use App\Filament\Resources\Municipalities\Pages\CreateMunicipality;
use App\Filament\Resources\Municipalities\Pages\EditMunicipality;
use App\Filament\Resources\Municipalities\Pages\ListMunicipalities;
use App\Filament\Resources\Municipalities\Schemas\MunicipalityForm;
use App\Filament\Resources\Municipalities\Tables\MunicipalitiesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * 1.9 del TODO: configuración de municipios — solo admin/superadmin, no un
 * moderador (es configuración estructural, no moderación de contenido).
 */
class MunicipalityResource extends Resource
{
    protected static ?string $model = Municipality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static UnitEnum|string|null $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'municipio';

    protected static ?string $pluralModelLabel = 'municipios';

    protected static ?string $navigationLabel = 'Municipios';

    public static function form(Schema $schema): Schema
    {
        return MunicipalityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MunicipalitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMunicipalities::route('/'),
            'create' => CreateMunicipality::route('/create'),
            'edit' => EditMunicipality::route('/{record}/edit'),
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
