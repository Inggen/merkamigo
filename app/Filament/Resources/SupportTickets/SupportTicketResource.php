<?php

namespace App\Filament\Resources\SupportTickets;

use App\Domain\Moderation\Models\SupportTicket;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Resources\SupportTickets\Tables\SupportTicketsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * 1.9 del TODO: gestión de solicitudes de soporte — solo listado con
 * acciones de cambio de estado, sin crear ni editar manualmente.
 */
class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static UnitEnum|string|null $navigationGroup = 'Moderación';

    protected static ?string $modelLabel = 'solicitud de soporte';

    protected static ?string $pluralModelLabel = 'solicitudes de soporte';

    protected static ?string $navigationLabel = 'Solicitudes de soporte';

    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
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

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasAnyPlatformRole(['superadmin']) ?? false;
    }
}
