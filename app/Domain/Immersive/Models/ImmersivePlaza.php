<?php

namespace App\Domain\Immersive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * IMM-012 del TODO inmersivo: una plaza es una instancia de capacidad
 * dentro de una experiencia (Plaza 1, Plaza 2...). Cada una es un espacio
 * físico propio, así que el punto de aparición, límites navegables, zonas
 * excluidas y calidad viven aquí — no en `ImmersiveExperience` (corrección
 * de arquitectura aplicada junto con IMM-012, ver TODO §4.2).
 *
 * @property array{x: float, y: float, z: float, rotationY: float}|null $spawn_point
 * @property array{minX: float, maxX: float, minZ: float, maxZ: float}|null $navigable_bounds
 * @property array{x: float, z: float}|null $orientation_center
 * @property array<int, array{points: array<int, array{x: float, z: float}>}>|null $excluded_zones
 */
class ImmersivePlaza extends Model
{
    protected $fillable = [
        'immersive_experience_id',
        'name',
        'order',
        'capacity',
        'category_rule',
        'status',
        'published_at',
        'spawn_point',
        'navigable_bounds',
        'orientation_center',
        'excluded_zones',
        'mobile_quality_profile',
        'desktop_quality_profile',
        'reference_image_path',
        'reference_image_width',
        'reference_image_height',
        'legend_image_path',
    ];

    protected function casts(): array
    {
        return [
            'spawn_point' => 'array',
            'navigable_bounds' => 'array',
            'orientation_center' => 'array',
            'excluded_zones' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ImmersiveExperience, $this>
     */
    public function experience(): BelongsTo
    {
        return $this->belongsTo(ImmersiveExperience::class, 'immersive_experience_id');
    }

    /**
     * @return HasMany<StandZone, $this>
     */
    public function zones(): HasMany
    {
        return $this->hasMany(StandZone::class);
    }

    /**
     * @return HasManyThrough<StandSlot, StandZone, $this>
     */
    public function slots(): HasManyThrough
    {
        return $this->hasManyThrough(StandSlot::class, StandZone::class);
    }

    /**
     * @return HasMany<ImmersivePlazaProp, $this>
     */
    public function props(): HasMany
    {
        return $this->hasMany(ImmersivePlazaProp::class);
    }

    /**
     * @return HasMany<PlazaLegendEntry, $this>
     */
    public function legendEntries(): HasMany
    {
        return $this->hasMany(PlazaLegendEntry::class);
    }

    /**
     * IMM-013 redefinido: todas las entradas de la leyenda deben estar
     * mapeadas a un objeto del catálogo antes de poder generar ubicaciones
     * a partir del plano.
     */
    public function legendIsFullyConfirmed(): bool
    {
        return $this->legendEntries()->count() > 0
            && $this->legendEntries()->where('status', '!=', 'confirmado')->doesntExist();
    }

    /**
     * IMM-013 redefinido: la imagen de referencia se asume que cubre
     * exactamente `navigable_bounds`, así que un punto en coordenadas de
     * imagen (origen arriba-izquierda, como en el DOM) se traduce a mundo
     * por calibración lineal. Devuelve `null` si falta la imagen, sus
     * dimensiones o los límites navegables — el llamador decide si eso
     * bloquea el guardado.
     *
     * @return array{x: float, z: float}|null
     */
    public function imageToWorld(float $imageX, float $imageY): ?array
    {
        $bounds = $this->navigable_bounds;

        if (blank($bounds) || blank($this->reference_image_width) || blank($this->reference_image_height)) {
            return null;
        }

        $ratioX = $imageX / $this->reference_image_width;
        $ratioZ = $imageY / $this->reference_image_height;

        return [
            'x' => $bounds['minX'] + $ratioX * ($bounds['maxX'] - $bounds['minX']),
            'z' => $bounds['minZ'] + $ratioZ * ($bounds['maxZ'] - $bounds['minZ']),
        ];
    }

    /**
     * Inversa de `imageToWorld()`: coordenadas de mundo → porcentaje
     * (0-100) dentro del rectángulo de `navigable_bounds`. Es lo que usa la
     * previsualización 2D (IMM-010) para dibujar zonas/slots/elementos
     * sobre la imagen de referencia sin depender de su tamaño en píxeles —
     * un `<svg viewBox="0 0 100 100">` superpuesto a la imagen alcanza.
     *
     * @return array{xPercent: float, yPercent: float}|null
     */
    public function worldToImagePercent(float $worldX, float $worldZ): ?array
    {
        $bounds = $this->navigable_bounds;

        if (blank($bounds)) {
            return null;
        }

        $rangeX = $bounds['maxX'] - $bounds['minX'];
        $rangeZ = $bounds['maxZ'] - $bounds['minZ'];

        if ($rangeX <= 0 || $rangeZ <= 0) {
            return null;
        }

        return [
            'xPercent' => (($worldX - $bounds['minX']) / $rangeX) * 100,
            'yPercent' => (($worldZ - $bounds['minZ']) / $rangeZ) * 100,
        ];
    }

    public function occupiedSlotsCount(): int
    {
        return $this->slots()->where('stand_slots.status', 'ocupada')->count();
    }

    public function hasCapacityAvailable(): bool
    {
        return $this->occupiedSlotsCount() < $this->capacity;
    }
}
