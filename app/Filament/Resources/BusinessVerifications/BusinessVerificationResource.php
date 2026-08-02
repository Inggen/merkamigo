<?php

namespace App\Filament\Resources\BusinessVerifications;

use App\Domain\Trust\Models\BusinessVerification;
use App\Filament\Resources\BusinessVerifications\Pages\EditBusinessVerification;
use App\Filament\Resources\BusinessVerifications\Pages\ListBusinessVerifications;
use App\Filament\Resources\BusinessVerifications\Schemas\BusinessVerificationForm;
use App\Filament\Resources\BusinessVerifications\Tables\BusinessVerificationsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class BusinessVerificationResource extends Resource
{
    protected static ?string $model = BusinessVerification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static UnitEnum|string|null $navigationGroup = 'Confianza';

    protected static ?string $modelLabel = 'verificación de negocio';

    protected static ?string $pluralModelLabel = 'verificaciones de negocio';

    protected static ?string $navigationLabel = 'Verificaciones de negocio';

    public static function form(Schema $schema): Schema
    {
        return BusinessVerificationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BusinessVerificationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinessVerifications::route('/'),
            'edit' => EditBusinessVerification::route('/{record}/edit'),
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
        return self::canViewAny();
    }
}
