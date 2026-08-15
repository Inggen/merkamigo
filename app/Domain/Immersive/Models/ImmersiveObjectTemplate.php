<?php

namespace App\Domain\Immersive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * IMM-013/IMM-020 del TODO inmersivo: catálogo único de todo lo que se
 * puede colocar en una plaza — construcciones (catedral, alcaldía, casa
 * colonial), stands, árboles por tipo/tamaño (pino, árbol grande, palma),
 * fuentes, monumentos y personajes. Es la versión administrable de lo que
 * hoy vive hardcodeado como `standardBuilders` en
 * `public/js/lib/voxel-plaza-engine.js`.
 *
 * En Fase 1 solo existen los metadatos (huella, eje frontal, categoría)
 * para poder referenciarlos desde el editor de zonas/slots y desde el
 * detector de colores del plano/leyenda (IMM-013 redefinido). El modelo 3D
 * real (`model_path`, `lod_config`) es IMM-020 en Fase 2 — hasta entonces
 * la plantilla es reservable/colocable pero no renderizable en la escena.
 *
 * `category = 'stand'` se reserva vía `StandSlot` (tiene flujo comercial de
 * asignación a vitrinas, IMM-022). El resto de categorías se colocan
 * directo como `ImmersivePlazaProp`, sin ese flujo.
 *
 * @property array<string, string>|null $allowed_colors
 * @property array<string, mixed>|null $lod_config
 * @property array<string, mixed>|null $model_definition
 */
class ImmersiveObjectTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'builder_key',
        'asset_input_mode',
        'model_definition',
        'ai_draft_definition',
        'ai_reference_images',
        'ai_instruction_log',
        'category',
        'max_width',
        'max_depth',
        'max_height',
        'max_boxes',
        'front_axis_rotation',
        'thumbnail_path',
        'allowed_colors',
        'model_path',
        'lod_config',
        'status',
    ];

    /**
     * Igual que el default de la columna en BD (`max_boxes`), pero también
     * en memoria: así una plantilla recién instanciada (sin guardar aún, o
     * en tests) refleja el mismo límite por defecto que tendría al
     * persistirse, sin depender de un round-trip a la base de datos.
     */
    protected $attributes = [
        'asset_input_mode' => 'model_3d',
        'max_width' => 1,
        'max_depth' => 1,
        'max_height' => 1,
        'max_boxes' => 40,
    ];

    protected function casts(): array
    {
        return [
            'asset_input_mode' => 'string',
            'max_width' => 'float',
            'max_depth' => 'float',
            'max_height' => 'float',
            'max_boxes' => 'integer',
            'front_axis_rotation' => 'float',
            'allowed_colors' => 'array',
            'lod_config' => 'array',
            'model_definition' => 'array',
            'ai_draft_definition' => 'array',
            'ai_reference_images' => 'array',
            'ai_instruction_log' => 'array',
        ];
    }

    /**
     * IMM-020/IMM-020b: renderizable si tiene un modelo GLB real
     * (`model_path`), una clave de `standardBuilders` procedural
     * (`builder_key`), o una definición generada por IA (`model_definition`,
     * interpretada por `buildFromDefinition` en el motor compartido) — los
     * tres mecanismos de renderizado que soporta el motor hoy.
     */
    public function isRenderable(): bool
    {
        return filled($this->model_path) || filled($this->builder_key) || filled($this->model_definition);
    }

    public function isStand(): bool
    {
        return $this->category === 'stand';
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : null;
    }

    public function modelPathUrl(): ?string
    {
        return $this->model_path ? Storage::disk('public')->url($this->model_path) : null;
    }

    public function referenceImagePath(string $view): ?string
    {
        return data_get($this->ai_reference_images, $view);
    }

    public function referenceImageUrl(string $view): ?string
    {
        $path = $this->referenceImagePath($view);

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /**
     * @return HasMany<StandSlot, $this>
     */
    public function slots(): HasMany
    {
        return $this->hasMany(StandSlot::class, 'stand_template_id');
    }

    /**
     * @return HasMany<ImmersivePlazaProp, $this>
     */
    public function props(): HasMany
    {
        return $this->hasMany(ImmersivePlazaProp::class, 'object_template_id');
    }

    /**
     * Pedido del usuario: duplicar un elemento del catálogo para partir de
     * uno existente en vez de configurar todo desde cero. A diferencia de
     * `ImmersiveExperience::duplicate()`, esta plantilla no tiene hijos
     * propios que clonar (`slots()`/`props()` son referencias ENTRANTES de
     * otras tablas, no composición) — copiar los campos propios basta.
     */
    public function duplicate(): self
    {
        return self::create([
            'name' => "{$this->name} (copia)",
            'slug' => $this->nextAvailableSlug(),
            'builder_key' => $this->builder_key,
            'asset_input_mode' => $this->asset_input_mode,
            'model_definition' => $this->model_definition,
            'ai_draft_definition' => $this->ai_draft_definition,
            'ai_reference_images' => $this->ai_reference_images,
            'ai_instruction_log' => $this->ai_instruction_log,
            'category' => $this->category,
            'max_width' => $this->max_width,
            'max_depth' => $this->max_depth,
            'max_height' => $this->max_height,
            'max_boxes' => $this->max_boxes,
            'front_axis_rotation' => $this->front_axis_rotation ?? 0,
            'thumbnail_path' => $this->thumbnail_path,
            'allowed_colors' => $this->allowed_colors,
            'model_path' => $this->model_path,
            'lod_config' => $this->lod_config,
            'status' => 'borrador',
        ]);
    }

    private function nextAvailableSlug(): string
    {
        $base = "{$this->slug}-copia";
        $slug = $base;
        $suffix = 2;

        while (self::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
