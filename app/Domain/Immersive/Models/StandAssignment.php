<?php

namespace App\Domain\Immersive\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IMM-021/IMM-022/IMM-023 del TODO inmersivo: relación persistente entre
 * una vitrina (`Business`) y su stand en una plaza. La crea y mantiene
 * `App\Domain\Immersive\Actions\{AssignBusinessToStand,ReleaseBusinessStand}`,
 * disparadas por `BusinessStandObserver` cada vez que cambia la
 * publicación del negocio — nunca se edita a mano desde un formulario de
 * posición/dimensiones (esas las decide el slot, no la asignación).
 *
 * Estados (IMM-023): `sin_configurar` (sin intento de asignación aún o
 * negocio no elegible), `pendiente` (elegible, pero su municipio no tiene
 * experiencia/plaza activa todavía), `publicado` (slot ocupado y visible),
 * `pausado` (tenía slot y lo perdió porque el negocio dejó de estar
 * publicado — `previous_slot_id` guarda cuál para intentar recuperarlo),
 * `sin_cupo` (elegible, hay plaza activa, pero ningún slot compatible
 * libre), `reubicacion_requerida` (tenía un slot válido que un admin
 * invalidó — ej. borró la zona — y hay que reasignar).
 */
class StandAssignment extends Model
{
    protected $fillable = [
        'business_id',
        'immersive_plaza_id',
        'stand_slot_id',
        'previous_slot_id',
        'object_template_id',
        'status',
        'motivo_reubicacion',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<ImmersivePlaza, $this>
     */
    public function plaza(): BelongsTo
    {
        return $this->belongsTo(ImmersivePlaza::class, 'immersive_plaza_id');
    }

    /**
     * @return BelongsTo<StandSlot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(StandSlot::class, 'stand_slot_id');
    }

    /**
     * @return BelongsTo<StandSlot, $this>
     */
    public function previousSlot(): BelongsTo
    {
        return $this->belongsTo(StandSlot::class, 'previous_slot_id');
    }

    /**
     * @return BelongsTo<ImmersiveObjectTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ImmersiveObjectTemplate::class, 'object_template_id');
    }

    public function isLive(): bool
    {
        return $this->status === 'publicado' && $this->stand_slot_id !== null;
    }
}
