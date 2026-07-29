<?php

namespace App\Filament\Resources\BusinessAttributes;

use App\Domain\Businesses\Models\BusinessAttribute;
use App\Filament\Resources\BusinessAttributes\Pages\CreateBusinessAttribute;
use App\Filament\Resources\BusinessAttributes\Pages\EditBusinessAttribute;
use App\Filament\Resources\BusinessAttributes\Pages\ListBusinessAttributes;
use App\Filament\Resources\BusinessAttributes\Schemas\BusinessAttributeForm;
use App\Filament\Resources\BusinessAttributes\Tables\BusinessAttributesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * 1.3 del TODO: vocabulario de "atributos administrables" del negocio
 * (ej. "Hecho en la región"), la misma configuración estructural que
 * municipios/categorías — solo admin/superadmin.
 */
class BusinessAttributeResource extends Resource
{
    protected static ?string $model = BusinessAttribute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static UnitEnum|string|null $navigationGroup = 'Configuración';

    public static function form(Schema $schema): Schema
    {
        return BusinessAttributeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BusinessAttributesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinessAttributes::route('/'),
            'create' => CreateBusinessAttribute::route('/create'),
            'edit' => EditBusinessAttribute::route('/{record}/edit'),
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
