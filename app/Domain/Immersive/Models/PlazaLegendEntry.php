<?php

namespace App\Domain\Immersive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IMM-013 del TODO inmersivo (redefinido): un color distinto detectado en
 * la imagen de leyenda de una plaza, pendiente de que el admin confirme
 * (o cree) a qué `ImmersiveObjectTemplate` corresponde. Solo cuando todas
 * las entradas de una plaza están `confirmado` se puede correr "Generar
 * ubicaciones" sobre el plano.
 */
class PlazaLegendEntry extends Model
{
    protected $fillable = [
        'immersive_plaza_id',
        'color_hex',
        'detected_pixel_count',
        'object_template_id',
        'status',
    ];

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
