<?php

namespace App\Filament\Resources\StandSlots;

use App\Domain\Immersive\Models\StandSlot;
use App\Filament\Resources\StandSlots\Pages\CreateStandSlot;
use App\Filament\Resources\StandSlots\Pages\EditStandSlot;
use App\Filament\Resources\StandSlots\Pages\ListStandSlots;
use App\Filament\Resources\StandSlots\Schemas\StandSlotForm;
use App\Filament\Resources\StandSlots\Tables\StandSlotsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * IMM-013 del TODO inmersivo: espacio exacto y validado donde puede
 * ubicarse un stand, dentro de una zona.
 */
class StandSlotResource extends Resource
{
    protected static ?string $model = StandSlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static UnitEnum|string|null $navigationGroup = 'Experiencias inmersivas';

    protected static ?string $modelLabel = 'slot de stand';

    protected static ?string $pluralModelLabel = 'slots de stand';

    protected static ?string $navigationLabel = 'Slots de stand';

    protected static ?string $navigationParentItem = 'Plazas';

    public static function form(Schema $schema): Schema
    {
        return StandSlotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StandSlotsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStandSlots::route('/'),
            'create' => CreateStandSlot::route('/create'),
            'edit' => EditStandSlot::route('/{record}/edit'),
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
