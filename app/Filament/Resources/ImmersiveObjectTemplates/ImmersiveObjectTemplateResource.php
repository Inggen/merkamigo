<?php

namespace App\Filament\Resources\ImmersiveObjectTemplates;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Filament\Resources\ImmersiveObjectTemplates\Pages\CreateImmersiveObjectTemplate;
use App\Filament\Resources\ImmersiveObjectTemplates\Pages\EditImmersiveObjectTemplate;
use App\Filament\Resources\ImmersiveObjectTemplates\Pages\EditImmersiveObjectTemplateSpatialEditor;
use App\Filament\Resources\ImmersiveObjectTemplates\Pages\GenerateWithAi;
use App\Filament\Resources\ImmersiveObjectTemplates\Pages\ListImmersiveObjectTemplates;
use App\Filament\Resources\ImmersiveObjectTemplates\Schemas\ImmersiveObjectTemplateForm;
use App\Filament\Resources\ImmersiveObjectTemplates\Tables\ImmersiveObjectTemplatesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * IMM-013/IMM-020 del TODO inmersivo: catálogo único de objetos
 * posicionables (construcciones, stands, árboles, fuentes, monumentos,
 * personajes). En Fase 1 solo se cargan metadatos; el modelo 3D real es
 * IMM-020 (Fase 2).
 */
class ImmersiveObjectTemplateResource extends Resource
{
    protected static ?string $model = ImmersiveObjectTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static UnitEnum|string|null $navigationGroup = 'Experiencias inmersivas';

    protected static ?string $modelLabel = 'objeto 3D';

    protected static ?string $pluralModelLabel = 'catálogo de objetos 3D';

    protected static ?string $navigationLabel = 'Catálogo de objetos 3D';

    public static function form(Schema $schema): Schema
    {
        return ImmersiveObjectTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImmersiveObjectTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImmersiveObjectTemplates::route('/'),
            'create' => CreateImmersiveObjectTemplate::route('/create'),
            'edit' => EditImmersiveObjectTemplate::route('/{record}/edit'),
            'object-editor' => EditImmersiveObjectTemplateSpatialEditor::route('/{record}/editar-objeto'),
            'generar-ia' => GenerateWithAi::route('/{record}/generar-ia'),
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
