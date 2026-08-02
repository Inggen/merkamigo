<?php

namespace App\Filament\Resources\BillingProducts;

use App\Domain\Billing\Models\BillingProduct;
use App\Filament\Resources\BillingProducts\Pages\CreateBillingProduct;
use App\Filament\Resources\BillingProducts\Pages\EditBillingProduct;
use App\Filament\Resources\BillingProducts\Pages\ListBillingProducts;
use App\Filament\Resources\BillingProducts\Schemas\BillingProductForm;
use App\Filament\Resources\BillingProducts\Tables\BillingProductsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * 4.3 del TODO: catálogo de productos de ingreso complementario
 * (destacados, vitrina asistida, kit arranca bonito) — precios y payload
 * editables desde administración, sin valores codificados.
 */
class BillingProductResource extends Resource
{
    protected static ?string $model = BillingProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static UnitEnum|string|null $navigationGroup = 'Cobro';

    protected static ?string $modelLabel = 'producto de cobro';

    protected static ?string $pluralModelLabel = 'productos de cobro';

    protected static ?string $navigationLabel = 'Productos de cobro';

    public static function form(Schema $schema): Schema
    {
        return BillingProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillingProducts::route('/'),
            'create' => CreateBillingProduct::route('/create'),
            'edit' => EditBillingProduct::route('/{record}/edit'),
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
