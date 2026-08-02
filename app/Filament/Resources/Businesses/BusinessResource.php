<?php

namespace App\Filament\Resources\Businesses;

use App\Domain\Businesses\Models\Business;
use App\Filament\Resources\Businesses\Pages\EditBusiness;
use App\Filament\Resources\Businesses\Pages\ListBusinesses;
use App\Filament\Resources\Businesses\Schemas\BusinessForm;
use App\Filament\Resources\Businesses\Tables\BusinessesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * 1.9 del TODO: revisión, publicación, suspensión y restauración de
 * vitrinas. No se crean negocios desde aquí (se crean vía el flujo normal
 * del emprendedor); solo se administran los ya existentes.
 */
class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static UnitEnum|string|null $navigationGroup = 'Moderación';

    protected static ?string $modelLabel = 'negocio';

    protected static ?string $pluralModelLabel = 'negocios';

    protected static ?string $navigationLabel = 'Negocios';

    public static function form(Schema $schema): Schema
    {
        return BusinessForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BusinessesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinesses::route('/'),
            'edit' => EditBusiness::route('/{record}/edit'),
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

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['moderator', 'admin', 'superadmin']) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['superadmin']) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['superadmin']) ?? false;
    }
}
