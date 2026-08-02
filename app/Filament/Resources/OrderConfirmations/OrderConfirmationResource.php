<?php

namespace App\Filament\Resources\OrderConfirmations;

use App\Domain\Trust\Models\OrderConfirmation;
use App\Filament\Resources\OrderConfirmations\Pages\ListOrderConfirmations;
use App\Filament\Resources\OrderConfirmations\Tables\OrderConfirmationsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrderConfirmationResource extends Resource
{
    protected static ?string $model = OrderConfirmation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static UnitEnum|string|null $navigationGroup = 'Confianza';

    protected static ?string $modelLabel = 'confirmación de pedido';

    protected static ?string $pluralModelLabel = 'confirmaciones de pedido';

    protected static ?string $navigationLabel = 'Confirmaciones de pedido';

    public static function table(Table $table): Table
    {
        return OrderConfirmationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderConfirmations::route('/'),
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
}
