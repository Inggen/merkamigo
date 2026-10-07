<?php

namespace App\Domain\Events\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuración de eventos de una vitrina (TODO_desarrollo_sistema_eventos_Merkamigo.md,
 * Fase 1). Una fila por negocio — `enabled` controla el módulo completo,
 * `public_events_enabled`/`private_reservations_enabled` controlan cada
 * mitad por separado porque un negocio puede querer publicar agenda sin
 * abrir reservas (o viceversa).
 *
 * @property array<string, array{closed: bool, open: ?string, close: ?string}>|null $weekly_schedule
 */
class EventSetting extends Model
{
    protected $fillable = [
        'business_id', 'enabled', 'public_events_enabled', 'private_reservations_enabled',
        'timezone', 'pricing_mode', 'hourly_rate_cents', 'max_capacity', 'min_advance_hours',
        'max_duration_hours', 'hold_minutes', 'weekly_schedule', 'policy_text', 'cancellation_text',
    ];

    public const PRICING_PLATOS = 'platos';

    public const PRICING_HORAS = 'horas';

    public const PRICING_HIBRIDO = 'hibrido';

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'public_events_enabled' => 'boolean',
            'private_reservations_enabled' => 'boolean',
            'weekly_schedule' => 'array',
            'hourly_rate_cents' => 'integer',
            'max_capacity' => 'integer',
            'min_advance_hours' => 'integer',
            'max_duration_hours' => 'integer',
            'hold_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
