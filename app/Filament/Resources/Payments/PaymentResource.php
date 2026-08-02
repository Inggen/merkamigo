<?php

namespace App\Filament\Resources\Payments;

use App\Domain\Billing\Models\Payment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * 4.2 del TODO: listado de pagos para conciliación, con acción de
 * reembolso solo para superadmin. Nunca se crea manualmente — todo pago
 * nace del checkout de Wompi.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'Cobro';

    protected static ?string $modelLabel = 'pago';

    protected static ?string $pluralModelLabel = 'pagos';

    protected static ?string $navigationLabel = 'Pagos';

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
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
