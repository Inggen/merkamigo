<?php

namespace App\Filament\Resources\WebhookSubscriptions;

use App\Domain\Platform\Models\WebhookSubscription;
use App\Filament\Resources\WebhookSubscriptions\Pages\CreateWebhookSubscription;
use App\Filament\Resources\WebhookSubscriptions\Pages\EditWebhookSubscription;
use App\Filament\Resources\WebhookSubscriptions\Pages\ListWebhookSubscriptions;
use App\Filament\Resources\WebhookSubscriptions\Schemas\WebhookSubscriptionForm;
use App\Filament\Resources\WebhookSubscriptions\Tables\WebhookSubscriptionsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * 5.4 del TODO: suscripciones de webhooks salientes para aliados —
 * gestión exclusiva de admin/superadmin, nunca visible al emprendedor.
 */
class WebhookSubscriptionResource extends Resource
{
    protected static ?string $model = WebhookSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static UnitEnum|string|null $navigationGroup = 'Plataforma';

    protected static ?string $modelLabel = 'suscripción de webhook';

    protected static ?string $pluralModelLabel = 'suscripciones de webhook';

    protected static ?string $navigationLabel = 'Suscripciones de webhook';

    public static function form(Schema $schema): Schema
    {
        return WebhookSubscriptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebhookSubscriptionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebhookSubscriptions::route('/'),
            'create' => CreateWebhookSubscription::route('/create'),
            'edit' => EditWebhookSubscription::route('/{record}/edit'),
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
