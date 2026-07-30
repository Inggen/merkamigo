<?php

namespace App\Filament\Resources\Needs;

use App\Domain\Needs\Models\Need;
use App\Filament\Resources\Needs\Pages\EditNeed;
use App\Filament\Resources\Needs\Pages\ListNeeds;
use App\Filament\Resources\Needs\Schemas\NeedForm;
use App\Filament\Resources\Needs\Tables\NeedsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * 2.1 del TODO: moderación de necesidades. No se crean desde aquí — las
 * publica el comprador desde "Pídelo en Merkamigo".
 */
class NeedResource extends Resource
{
    protected static ?string $model = Need::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static UnitEnum|string|null $navigationGroup = 'Moderación';

    public static function form(Schema $schema): Schema
    {
        return NeedForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NeedsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNeeds::route('/'),
            'edit' => EditNeed::route('/{record}/edit'),
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
