<?php

namespace App\Filament\Resources\Subscriptions;

use App\Domain\Billing\Models\Subscription;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Resources\Subscriptions\Tables\SubscriptionsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * 4.1 del TODO: el staff cambia de plan, extiende la prueba, suspende,
 * reactiva o cancela la suscripción de un negocio. Sin creación manual —
 * toda suscripción nace de `SubscribeToPlan` (negocio nuevo o pago
 * aprobado).
 */
class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Cobro';

    protected static ?string $modelLabel = 'suscripción';

    protected static ?string $pluralModelLabel = 'suscripciones';

    protected static ?string $navigationLabel = 'Suscripciones';

    public static function table(Table $table): Table
    {
        return SubscriptionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
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
