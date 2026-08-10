<?php

namespace App\Domain\Immersive\Models;

use App\Domain\Immersive\Support\SpatialGeometry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * IMM-013 del TODO inmersivo: polígono permitido dentro de una plaza donde
 * se pueden reservar `stand_slots`.
 *
 * @property array{points: array<int, array{x: float, z: float}>} $polygon
 * @property array{x: float, z: float}|null $reference_center
 */
class StandZone extends Model
{
    protected $fillable = [
        'immersive_plaza_id',
        'name',
        'polygon',
        'default_orientation',
        'reference_center',
        'min_separation',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'polygon' => 'array',
            'reference_center' => 'array',
            'min_separation' => 'float',
        ];
    }

    /**
     * §5 del TODO: "validar polígonos, tamaños, rutas y colisiones antes
     * de guardar". Una zona debe caber dentro de los límites navegables de
     * su plaza y no pisar ninguna de sus zonas excluidas.
     */
    protected static function booted(): void
    {
        static::saving(function (self $zone): void {
            $plaza = $zone->plaza ?? ImmersivePlaza::find($zone->immersive_plaza_id);

            if (! $plaza || blank($plaza->navigable_bounds)) {
                return;
            }

            if (! SpatialGeometry::boundsContainPolygon($plaza->navigable_bounds, $zone->polygon)) {
                throw ValidationException::withMessages([
                    'polygon' => 'El polígono de la zona sale de los límites navegables de la plaza.',
                ]);
            }

            foreach ($plaza->excluded_zones ?? [] as $excluded) {
                if (SpatialGeometry::polygonsIntersect($zone->polygon, $excluded)) {
                    throw ValidationException::withMessages([
                        'polygon' => 'El polígono de la zona invade una zona excluida de la plaza (ruta, monumento o punto de aparición).',
                    ]);
                }
            }
        });
    }

    /**
     * @return BelongsTo<ImmersivePlaza, $this>
     */
    public function plaza(): BelongsTo
    {
        return $this->belongsTo(ImmersivePlaza::class, 'immersive_plaza_id');
    }

    /**
     * @return HasMany<StandSlot, $this>
     */
    public function slots(): HasMany
    {
        return $this->hasMany(StandSlot::class);
    }
}
