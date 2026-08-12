<?php

namespace App\Domain\Immersive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IMM-013 del TODO inmersivo (redefinido): un objeto decorativo o
 * estructural colocado directamente en una plaza — construcción, árbol,
 * fuente, monumento o personaje del catálogo (`ImmersiveObjectTemplate`
 * con `category != 'stand'`). A diferencia de `StandSlot`, no tiene flujo
 * de asignación comercial: se coloca y ya.
 *
 * @property array{x: float, y: float}|null $image_position
 * @property array{x: float, y: float, z: float} $world_position
 * @property array{x: float, y: float, z: float}|null $rotation
 * @property array{x: float, y: float, z: float}|null $scale_vector
 * @property bool $collision_enabled
 */
class ImmersivePlazaProp extends Model
{
    protected $fillable = [
        'immersive_plaza_id',
        'object_template_id',
        'image_position',
        'world_position',
        'rotation',
        'scale',
        'scale_vector',
        'collision_enabled',
        'source',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'image_position' => 'array',
            'world_position' => 'array',
            'rotation' => 'array',
            'scale' => 'float',
            'scale_vector' => 'array',
            'collision_enabled' => 'boolean',
        ];
    }

    /**
     * Escala efectiva por eje. Si la fila viene de antes del soporte
     * vectorial, cae al `scale` uniforme legado.
     *
     * @return array{x: float, y: float, z: float}
     */
    public function scaleVector(): array
    {
        if (
            is_array($this->scale_vector)
            && isset($this->scale_vector['x'], $this->scale_vector['y'], $this->scale_vector['z'])
        ) {
            return [
                'x' => (float) $this->scale_vector['x'],
                'y' => (float) $this->scale_vector['y'],
                'z' => (float) $this->scale_vector['z'],
            ];
        }

        $scale = (float) ($this->scale ?: 1);

        return ['x' => $scale, 'y' => $scale, 'z' => $scale];
    }

    /**
     * @return BelongsTo<ImmersivePlaza, $this>
     */
    public function plaza(): BelongsTo
    {
        return $this->belongsTo(ImmersivePlaza::class, 'immersive_plaza_id');
    }

    /**
     * @return BelongsTo<ImmersiveObjectTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ImmersiveObjectTemplate::class, 'object_template_id');
    }
}
