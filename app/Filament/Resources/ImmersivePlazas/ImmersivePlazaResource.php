<?php

namespace App\Filament\Resources\ImmersivePlazas;

use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Filament\Resources\ImmersivePlazas\Pages\CreateImmersivePlaza;
use App\Filament\Resources\ImmersivePlazas\Pages\EditImmersivePlaza;
use App\Filament\Resources\ImmersivePlazas\Pages\EditImmersivePlazaSpatialEditor;
use App\Filament\Resources\ImmersivePlazas\Pages\ListImmersivePlazas;
use App\Filament\Resources\ImmersivePlazas\RelationManagers\PropsRelationManager;
use App\Filament\Resources\ImmersivePlazas\Schemas\ImmersivePlazaForm;
use App\Filament\Resources\ImmersivePlazas\Tables\ImmersivePlazasTable;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * IMM-012 del TODO inmersivo: plazas/páginas de capacidad dentro de una
 * experiencia (Plaza 1, Plaza 2...). Cada plaza es su propia instancia
 * física: punto de aparición, límites navegables, calidad e imagen de
 * referencia le pertenecen a ella, no a la experiencia.
 */
class ImmersivePlazaResource extends Resource
{
    protected static ?string $model = ImmersivePlaza::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static UnitEnum|string|null $navigationGroup = 'Experiencias inmersivas';

    protected static ?string $modelLabel = 'plaza';

    protected static ?string $pluralModelLabel = 'plazas';

    protected static ?string $navigationLabel = 'Plazas';

    public static function form(Schema $schema): Schema
    {
        return ImmersivePlazaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImmersivePlazasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImmersivePlazas::route('/'),
            'create' => CreateImmersivePlaza::route('/create'),
            'edit' => EditImmersivePlaza::route('/{record}/edit'),
            'spatial-editor' => EditImmersivePlazaSpatialEditor::route('/{record}/editor-espacial'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            PropsRelationManager::class,
        ];
    }

    /**
     * Pedido explícito del usuario: "el botón de vista previa" debe dejarlo
     * entrar y caminar la experiencia real, no solo ver el plano 2D de
     * arriba. Abre la escena tal cual quedaría antes de publicar
     * (`ImmersiveExperience::previewUrl()`, protegido para administradores
     * — ver `PlazaController`).
     */
    public static function enterExperienceAction(): Action
    {
        return Action::make('enterExperience')
            ->label('Entrar a la experiencia')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->url(fn (ImmersivePlaza $record): ?string => $record->experience?->previewUrl())
            ->openUrlInNewTab()
            ->visible(fn (ImmersivePlaza $record): bool => filled($record->experience?->previewUrl()));
    }

    public static function spatialEditorAction(): Action
    {
        return Action::make('spatialEditor')
            ->label('Editor espacial (3D)')
            ->icon(Heroicon::OutlinedCube)
            ->color('gray')
            ->url(fn (ImmersivePlaza $record): string => static::getUrl('spatial-editor', ['record' => $record]))
            ->visible(fn (ImmersivePlaza $record): bool => static::canEdit($record));
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
