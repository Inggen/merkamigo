<?php

namespace App\Filament\Resources\AuditLogs;

use App\Domain\Platform\Models\AuditLog;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * 1.9 del TODO: consulta de auditoría, de solo lectura. Sensible — solo
 * admin/superadmin, no un moderador.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static UnitEnum|string|null $navigationGroup = 'Plataforma';

    protected static ?string $modelLabel = 'registro de auditoría';

    protected static ?string $pluralModelLabel = 'registros de auditoría';

    protected static ?string $navigationLabel = 'Registros de auditoría';

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
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

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
