<?php

namespace App\Filament\Resources\ImmersiveExperiences;

use App\Domain\Immersive\Models\ImmersiveExperience;
use App\Filament\Resources\ImmersiveExperiences\Pages\CreateImmersiveExperience;
use App\Filament\Resources\ImmersiveExperiences\Pages\EditImmersiveExperience;
use App\Filament\Resources\ImmersiveExperiences\Pages\ListImmersiveExperiences;
use App\Filament\Resources\ImmersiveExperiences\RelationManagers\VersionsRelationManager;
use App\Filament\Resources\ImmersiveExperiences\Schemas\ImmersiveExperienceForm;
use App\Filament\Resources\ImmersiveExperiences\Tables\ImmersiveExperiencesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * IMM-010/IMM-011 del TODO inmersivo — CRUD de experiencias, solo
 * admin/superadmin (es configuración estructural por municipio, igual que
 * `MunicipalityResource`).
 */
class ImmersiveExperienceResource extends Resource
{
    protected static ?string $model = ImmersiveExperience::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static UnitEnum|string|null $navigationGroup = 'Experiencias inmersivas';

    protected static ?string $modelLabel = 'experiencia inmersiva';

    protected static ?string $pluralModelLabel = 'experiencias inmersivas';

    protected static ?string $navigationLabel = 'Experiencias';

    public static function form(Schema $schema): Schema
    {
        return ImmersiveExperienceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImmersiveExperiencesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImmersiveExperiences::route('/'),
            'create' => CreateImmersiveExperience::route('/create'),
            'edit' => EditImmersiveExperience::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            VersionsRelationManager::class,
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
