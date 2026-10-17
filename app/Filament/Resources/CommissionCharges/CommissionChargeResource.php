<?php

namespace App\Filament\Resources\CommissionCharges;

use App\Domain\Marketplace\Models\CommissionCharge;
use App\Filament\Resources\CommissionCharges\Pages\ListCommissionCharges;
use App\Filament\Resources\CommissionCharges\Tables\CommissionChargesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Panel de conciliación de comisiones del Marketplace (PR2 de
 * TODO_VENTAS_RENTABILIDAD.md, hallazgo #2 de la auditoría,
 * docs/auditoria-ventas-rentabilidad.md §1.4: "ningún superadmin puede
 * ver desde /admin el GMV, las comisiones devengadas/cobradas/fallidas
 * ni los pedidos pagados reales"). Nunca se crea ni edita a mano — nace
 * siempre de `AccrueCommission` sobre pedidos pagados reales.
 */
class CommissionChargeResource extends Resource
{
    protected static ?string $model = CommissionCharge::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static UnitEnum|string|null $navigationGroup = 'Cobro';

    protected static ?string $modelLabel = 'comisión de marketplace';

    protected static ?string $pluralModelLabel = 'comisiones de marketplace';

    protected static ?string $navigationLabel = 'Comisiones de marketplace';

    public static function table(Table $table): Table
    {
        return CommissionChargesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommissionCharges::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['admin', 'superadmin']) ?? false;
    }
}
