<?php

namespace App\Domain\Immersive\Models;

use App\Domain\Discovery\Models\Category;
use App\Domain\Immersive\Support\SpatialGeometry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

/**
 * IMM-013 del TODO inmersivo: espacio exacto y validado donde puede
 * ubicarse un stand. Guarda tanto la posición sobre la imagen de
 * referencia de la plaza (para redibujar la reserva en el editor) como la
 * posición ya calculada en coordenadas de mundo (para las validaciones y
 * para el motor 3D) — ver la redefinición de IMM-013 en el TODO.
 *
 * @property array{x: float, y: float}|null $image_position
 * @property array{x: float, y: float, z: float} $world_position
 * @property array{x: float, y: float, z: float}|null $rotation
 */
class StandSlot extends Model
{
    protected $fillable = [
        'stand_zone_id',
        'code',
        'stand_template_id',
        'allowed_category_id',
        'image_position',
        'world_position',
        'rotation',
        'max_width',
        'max_depth',
        'orientation_mode',
        'accessible',
        'status',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'image_position' => 'array',
            'world_position' => 'array',
            'rotation' => 'array',
            'max_width' => 'float',
            'max_depth' => 'float',
            'accessible' => 'boolean',
        ];
    }

    /**
     * §5 del TODO — "Reglas obligatorias" y "validar polígonos, tamaños,
     * rutas y colisiones antes de guardar" (IMM-013). Se ejecuta en cada
     * guardado, no solo al crear, porque mover o redimensionar un slot
     * existente puede volverlo inválido igual que uno nuevo.
     */
    protected static function booted(): void
    {
        static::saving(function (self $slot): void {
            $slot->resolveWorldPositionFromImage();
            $slot->validateAgainstZoneTemplateAndNeighbors();
        });
    }

    /**
     * Si el slot se ubicó haciendo clic sobre la imagen de referencia
     * (`image_position`) y todavía no tiene `world_position`, la calcula
     * por calibración lineal usando la plaza dueña de la zona.
     */
    private function resolveWorldPositionFromImage(): void
    {
        if (filled($this->world_position) || blank($this->image_position)) {
            return;
        }

        $zone = $this->zone ?? StandZone::find($this->stand_zone_id);
        $world = $zone?->plaza?->imageToWorld($this->image_position['x'], $this->image_position['y']);

        if ($world) {
            $this->world_position = ['x' => $world['x'], 'y' => 0, 'z' => $world['z']];
        }
    }

    private function validateAgainstZoneTemplateAndNeighbors(): void
    {
        if (blank($this->world_position)) {
            throw ValidationException::withMessages([
                'world_position' => 'El slot necesita una posición de mundo: directa, o calculada desde su posición en la imagen de referencia de la plaza (que a su vez requiere límites navegables e imagen ya configurados).',
            ]);
        }

        $zone = $this->zone ?? StandZone::find($this->stand_zone_id);

        if (! $zone) {
            return;
        }

        $footprint = [
            'x' => $this->world_position['x'],
            'z' => $this->world_position['z'],
            'width' => $this->max_width,
            'depth' => $this->max_depth,
        ];

        if (! SpatialGeometry::polygonContainsRectangle($zone->polygon, $footprint)) {
            throw ValidationException::withMessages([
                'world_position' => 'La huella del slot sale del polígono de su zona.',
            ]);
        }

        $plaza = $zone->plaza;

        foreach ($plaza->excluded_zones ?? [] as $excluded) {
            if (SpatialGeometry::polygonIntersectsRectangle($excluded, $footprint)) {
                throw ValidationException::withMessages([
                    'world_position' => 'La huella del slot invade una zona excluida de la plaza (ruta, monumento, acceso o punto de aparición).',
                ]);
            }
        }

        if ($this->stand_template_id) {
            $template = $this->template ?? ImmersiveObjectTemplate::find($this->stand_template_id);

            if ($template && ($this->max_width > $template->max_width || $this->max_depth > $template->max_depth)) {
                throw ValidationException::withMessages([
                    'stand_template_id' => 'La huella declarada del slot es mayor que la huella máxima de la plantilla seleccionada.',
                ]);
            }
        }

        $margin = $zone->min_separation;

        $neighbors = $zone->slots()
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->where('status', '!=', 'bloqueada')
            ->get();

        foreach ($neighbors as $neighbor) {
            $neighborFootprint = [
                'x' => $neighbor->world_position['x'],
                'z' => $neighbor->world_position['z'],
                'width' => $neighbor->max_width,
                'depth' => $neighbor->max_depth,
            ];

            if (SpatialGeometry::rectanglesOverlap($footprint, $neighborFootprint, $margin)) {
                throw ValidationException::withMessages([
                    'world_position' => "El slot se solapa, o queda a menos de {$margin}m, del slot \"{$neighbor->code}\".",
                ]);
            }
        }
    }

    /**
     * @return BelongsTo<StandZone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(StandZone::class, 'stand_zone_id');
    }

    /**
     * @return BelongsTo<ImmersiveObjectTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ImmersiveObjectTemplate::class, 'stand_template_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function allowedCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'allowed_category_id');
    }

    /**
     * @return HasOne<StandAssignment, $this>
     */
    public function assignment(): HasOne
    {
        return $this->hasOne(StandAssignment::class, 'stand_slot_id');
    }
}
