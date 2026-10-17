<?php

namespace App\Filament\Resources\Orders;

use App\Domain\Marketplace\Models\Order;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Pedidos reales del Marketplace (PR2 de TODO_VENTAS_RENTABILIDAD.md,
 * hallazgo #2 de la auditoría, docs/auditoria-ventas-rentabilidad.md
 * §1.4): antes no había ninguna forma de ver desde /admin qué pedidos se
 * pagaron realmente — solo existía `OrderConfirmationResource`, que es
 * un modelo distinto (una constancia sin pago en línea). Nunca se crea
 * ni edita a mano — nace siempre del checkout real contra Wompi.
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static UnitEnum|string|null $navigationGroup = 'Cobro';

    protected static ?string $modelLabel = 'pedido de marketplace';

    protected static ?string $pluralModelLabel = 'pedidos de marketplace';

    protected static ?string $navigationLabel = 'Pedidos (marketplace)';

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
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
