<?php

namespace App\Filament\Resources\LoyaltyEnrollments;

use App\Domain\Loyalty\Models\LoyaltyEnrollment;
use App\Filament\Resources\LoyaltyEnrollments\Pages\ListLoyaltyEnrollments;
use App\Filament\Resources\LoyaltyEnrollments\Tables\LoyaltyEnrollmentsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * TODO_Merkapuntos.md, F4.3: panel de administración de Merkamigo Premia
 * — "flags por negocio" (el estado de cada adhesión), con la auditoría
 * ya cubierta por `AuditLogResource` (reutilizado, no duplicado: toda
 * acción de Merkapuntos ya pasa por `RecordAuditLog`). Sin formulario de
 * edición libre a propósito — los únicos cambios permitidos son
 * suspender/reactivar con motivo, vía las acciones de la tabla.
 */
class LoyaltyEnrollmentResource extends Resource
{
    protected static ?string $model = LoyaltyEnrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static UnitEnum|string|null $navigationGroup = 'Plataforma';

    protected static ?string $modelLabel = 'adhesión a Merkamigo Premia';

    protected static ?string $pluralModelLabel = 'Merkamigo Premia';

    protected static ?string $navigationLabel = 'Merkamigo Premia';

    public static function table(Table $table): Table
    {
        return LoyaltyEnrollmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoyaltyEnrollments::route('/'),
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
