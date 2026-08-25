<?php

namespace App\Filament\Resources\ImmersivePlazaProps;

use App\Domain\Immersive\Models\ImmersivePlazaProp;
use App\Filament\Resources\ImmersivePlazaProps\Pages\CreateImmersivePlazaProp;
use App\Filament\Resources\ImmersivePlazaProps\Pages\EditImmersivePlazaProp;
use App\Filament\Resources\ImmersivePlazaProps\Pages\ListImmersivePlazaProps;
use App\Filament\Resources\ImmersivePlazaProps\RelationManagers\AdsRelationManager;
use App\Filament\Resources\ImmersivePlazaProps\Schemas\ImmersivePlazaPropForm;
use App\Filament\Resources\ImmersivePlazaProps\Tables\ImmersivePlazaPropsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * IMM-013 del TODO inmersivo (redefinido): construcciones, árboles,
 * fuentes, monumentos y personajes colocados en una plaza — a mano o por
 * la acción "Generar ubicaciones" a partir del plano + leyenda.
 */
class ImmersivePlazaPropResource extends Resource
{
    protected static ?string $model = ImmersivePlazaProp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static UnitEnum|string|null $navigationGroup = 'Experiencias inmersivas';

    protected static ?string $modelLabel = 'elemento de plaza';

    protected static ?string $pluralModelLabel = 'elementos de plaza';

    protected static ?string $navigationLabel = 'Elementos de plaza';

    protected static ?string $navigationParentItem = 'Plazas';

    public static function form(Schema $schema): Schema
    {
        return ImmersivePlazaPropForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImmersivePlazaPropsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImmersivePlazaProps::route('/'),
            'create' => CreateImmersivePlazaProp::route('/create'),
            'edit' => EditImmersivePlazaProp::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            AdsRelationManager::class,
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
