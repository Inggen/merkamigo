<?php

namespace App\Domain\Immersive\Models;

use App\Domain\Discovery\Models\Municipality;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/**
 * IMM-010/IMM-011 del TODO inmersivo: configuración y estado publicable de
 * una plaza voxel por municipio. La escena en sí (geometría, texturas) sigue
 * siendo código/assets fijos por municipio (decisión de arquitectura #1 del
 * TODO) — esta tabla no la reemplaza, solo guarda a qué municipio pertenece
 * y qué escena la sirve. El punto de aparición, límites navegables y demás
 * configuración espacial viven en `ImmersivePlaza` (IMM-012): una
 * experiencia puede tener varias plazas y cada una es una instancia física
 * distinta.
 */
class ImmersiveExperience extends Model
{
    protected $fillable = [
        'municipality_id',
        'name',
        'slug',
        'route_name',
        'description',
        'status',
        'thumbnail_path',
        'published_version_id',
    ];

    /**
     * Reglas de negocio de IMM-010/IMM-011, aplicadas en `saving()` (no solo
     * en `publish()`) para que se cumplan sin importar el camino: acción
     * "Publicar versión", formulario del admin, seeder o un test.
     *
     * Solo se validan al MOMENTO DE PASAR a `publicada` (registro nuevo, o
     * `status` cambiando desde otro valor) — no en cada guardado posterior
     * de un registro que ya estaba publicado. Eloquent dispara `saving()`
     * en todo `save()`, tenga o no cambios; sin este resguardo, editar
     * cualquier campo de una experiencia ya publicada (o simplemente
     * volver a correr un seeder idempotente) fallaría la validación de
     * "plaza lista" aunque nada relacionado con publicar haya cambiado.
     */
    protected static function booted(): void
    {
        static::saving(function (self $experience): void {
            if ($experience->status !== 'publicada') {
                return;
            }

            if ($experience->exists && ! $experience->isDirty('status')) {
                return;
            }

            $experience->assertReadyToPublish();

            $alreadyPublished = static::query()
                ->where('municipality_id', $experience->municipality_id)
                ->where('status', 'publicada')
                ->when($experience->exists, fn ($query) => $query->whereKeyNot($experience->getKey()))
                ->exists();

            if ($alreadyPublished) {
                throw ValidationException::withMessages([
                    'status' => 'Ya existe una experiencia publicada para este municipio. Pausa o archiva la anterior antes de publicar esta.',
                ]);
            }
        });
    }

    /**
     * IMM-010 — "no publicar sin escena, punto de aparición y límites".
     * Reescrito tras IMM-012: el punto de aparición y los límites ya no
     * viven en la experiencia sino en cada `ImmersivePlaza`, así que la
     * regla es "al menos una plaza lista", no "la experiencia misma".
     */
    private function assertReadyToPublish(): void
    {
        if (blank($this->route_name)) {
            throw ValidationException::withMessages([
                'route_name' => 'No se puede publicar sin asignar una escena inmersiva.',
            ]);
        }

        $hasReadyPlaza = $this->exists
            ? $this->plazas()->whereNotNull('spawn_point')->whereNotNull('navigable_bounds')->exists()
            : false;

        if (! $hasReadyPlaza) {
            throw ValidationException::withMessages([
                'status' => 'No se puede publicar sin al menos una plaza con punto de aparición y límites navegables definidos.',
            ]);
        }
    }

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @return HasMany<ImmersivePlaza, $this>
     */
    public function plazas(): HasMany
    {
        return $this->hasMany(ImmersivePlaza::class)->orderBy('order');
    }

    /**
     * @return HasMany<ExperienceVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ExperienceVersion::class);
    }

    /**
     * @return BelongsTo<ExperienceVersion, $this>
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(ExperienceVersion::class, 'published_version_id');
    }

    /**
     * URL pública de la escena que sirve esta experiencia, o `null` si no
     * tiene una escena asignada todavía (o la ruta configurada ya no
     * existe). Reemplaza el `match($slug)` hardcodeado que antes vivía en
     * `Municipality::immersiveLabUrl()`.
     *
     * Siempre se pasa `municipio` — la única de las 3 escenas que lo
     * necesita es la genérica (`labs.generic-plaza`, con `{municipio:slug}`
     * en la ruta); las otras dos no declaran ese parámetro, así que
     * Laravel simplemente lo ignora (lo agrega como query string inofensivo
     * en vez de fallar). Evita un `if` por tipo de escena.
     */
    public function labUrl(): ?string
    {
        if (blank($this->route_name) || ! Route::has($this->route_name)) {
            return null;
        }

        return route($this->route_name, ['municipio' => $this->municipality?->slug]);
    }

    /**
     * Igual que `labUrl()`, pero pide entrar tal cual quedaría la
     * experiencia antes de publicar (`?preview=1`) — `PlazaController`
     * exige que quien la abra sea un administrador autenticado, así que
     * este enlace nunca expone borradores al público.
     */
    public function previewUrl(): ?string
    {
        if (blank($this->route_name) || ! Route::has($this->route_name)) {
            return null;
        }

        return route($this->route_name, ['municipio' => $this->municipality?->slug, 'preview' => 1]);
    }

    /**
     * Snapshot de los campos versionables tal como están ahora mismo,
     * incluyendo la configuración espacial de cada plaza hija, para
     * guardarlos en `experience_versions.config_snapshot` al publicar
     * (IMM-014). Una reversión restaura los campos de la experiencia y, por
     * plaza (emparejando por id), los campos espaciales — las plazas
     * borradas después de esta versión no se resucitan, ver
     * `ImmersiveExperience::revertToVersion()`.
     *
     * @return array<string, mixed>
     */
    public function versionableConfig(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'plazas' => $this->plazas()->get()->map(fn (ImmersivePlaza $plaza): array => [
                'id' => $plaza->id,
                'name' => $plaza->name,
                'order' => $plaza->order,
                'capacity' => $plaza->capacity,
                'category_rule' => $plaza->category_rule,
                'spawn_point' => $plaza->spawn_point,
                'navigable_bounds' => $plaza->navigable_bounds,
                'orientation_center' => $plaza->orientation_center,
                'excluded_zones' => $plaza->excluded_zones,
                'mobile_quality_profile' => $plaza->mobile_quality_profile,
                'desktop_quality_profile' => $plaza->desktop_quality_profile,
                'fog' => $plaza->fog,
            ])->all(),
        ];
    }

    /**
     * IMM-014: única vía para que una experiencia quede en estado
     * "publicada" — siempre deja un `ExperienceVersion` con foto fija de la
     * configuración, para que la publicación sea reversible.
     */
    public function publish(?User $author): ExperienceVersion
    {
        // Validar antes de crear nada: si esto no fuera lo primero, un
        // intento de publicar sin plaza lista dejaría una `ExperienceVersion`
        // huérfana (el `saving()` de más abajo recién dispararía al hacer
        // `$this->update(['status' => 'publicada'])`, después de crearla).
        $this->assertReadyToPublish();

        return DB::transaction(function () use ($author): ExperienceVersion {
            $nextVersion = ($this->versions()->max('version') ?? 0) + 1;
            $snapshot = $this->versionableConfig();

            $version = $this->versions()->create([
                'version' => $nextVersion,
                'config_snapshot' => $snapshot,
                'checksum' => hash('sha256', json_encode($snapshot) ?: ''),
                'status' => 'publicada',
                'author_id' => $author?->id,
                'published_at' => now(),
            ]);

            $this->update([
                'status' => 'publicada',
                'published_version_id' => $version->id,
            ]);

            // Un elemento en "borrador" solo se ve con `?preview=1` (admin).
            // Publicar la experiencia es la señal de "esto ya está listo
            // para el público" — así que confirma de una vez los elementos
            // pendientes de cada plaza, en vez de dejarlos invisibles hasta
            // que alguien los edite uno por uno en otra pantalla.
            ImmersivePlazaProp::query()
                ->whereIn('immersive_plaza_id', $this->plazas()->pluck('id'))
                ->where('status', 'borrador')
                ->update(['status' => 'confirmado']);

            return $version;
        });
    }

    /**
     * IMM-014: revertir restaura los campos de esa versión sobre el
     * borrador actual y la vuelve a publicar como una versión NUEVA (no se
     * reescribe el historial) — así la reversión también queda auditada.
     * Las plazas se emparejan por `id`; una plaza borrada después de que se
     * tomó esa versión no se vuelve a crear (limitación documentada).
     */
    public function revertToVersion(ExperienceVersion $version, ?User $author): ExperienceVersion
    {
        $snapshot = $version->config_snapshot;

        $this->name = $snapshot['name'] ?? $this->name;
        $this->description = $snapshot['description'] ?? $this->description;
        $this->save();

        $plazasById = $this->plazas()->get()->keyBy('id');

        foreach ($snapshot['plazas'] ?? [] as $plazaSnapshot) {
            $plaza = $plazasById->get($plazaSnapshot['id'] ?? null);

            if (! $plaza) {
                continue;
            }

            $plaza->update([
                'name' => $plazaSnapshot['name'] ?? $plaza->name,
                'order' => $plazaSnapshot['order'] ?? $plaza->order,
                'capacity' => $plazaSnapshot['capacity'] ?? $plaza->capacity,
                'category_rule' => $plazaSnapshot['category_rule'] ?? $plaza->category_rule,
                'spawn_point' => $plazaSnapshot['spawn_point'] ?? null,
                'navigable_bounds' => $plazaSnapshot['navigable_bounds'] ?? null,
                'orientation_center' => $plazaSnapshot['orientation_center'] ?? null,
                'excluded_zones' => $plazaSnapshot['excluded_zones'] ?? null,
                'mobile_quality_profile' => $plazaSnapshot['mobile_quality_profile'] ?? $plaza->mobile_quality_profile,
                'desktop_quality_profile' => $plazaSnapshot['desktop_quality_profile'] ?? $plaza->desktop_quality_profile,
                'fog' => $plazaSnapshot['fog'] ?? $plaza->fog,
            ]);
        }

        return $this->publish($author);
    }

    /**
     * IMM-010 — "el administrador crea, edita, DUPLICA...". Clona la
     * experiencia completa (plazas, zonas, slots, elementos) como borrador
     * nuevo, para partir de una plaza existente en vez de configurar todo
     * desde cero. No duplica el historial de versiones ni las entradas de
     * leyenda pendientes — son artefactos de UNA publicación/detección
     * concreta, no configuración a reutilizar.
     */
    public function duplicate(): self
    {
        return DB::transaction(function (): self {
            $copy = self::create([
                'municipality_id' => $this->municipality_id,
                'name' => "{$this->name} (copia)",
                'slug' => $this->nextAvailableSlug(),
                'route_name' => $this->route_name,
                'description' => $this->description,
                'status' => 'borrador',
                'thumbnail_path' => $this->thumbnail_path,
            ]);

            foreach ($this->plazas()->with('zones.slots', 'props')->get() as $plaza) {
                $plazaCopy = $copy->plazas()->create([
                    'name' => $plaza->name,
                    'order' => $plaza->order,
                    'capacity' => $plaza->capacity,
                    'category_rule' => $plaza->category_rule,
                    'status' => 'borrador',
                    'spawn_point' => $plaza->spawn_point,
                    'navigable_bounds' => $plaza->navigable_bounds,
                    'orientation_center' => $plaza->orientation_center,
                    'excluded_zones' => $plaza->excluded_zones,
                    'mobile_quality_profile' => $plaza->mobile_quality_profile,
                    'desktop_quality_profile' => $plaza->desktop_quality_profile,
                    'reference_image_path' => $plaza->reference_image_path,
                    'reference_image_width' => $plaza->reference_image_width,
                    'reference_image_height' => $plaza->reference_image_height,
                    'legend_image_path' => $plaza->legend_image_path,
                    'fog' => $plaza->fog,
                ]);

                foreach ($plaza->zones as $zone) {
                    $zoneCopy = $plazaCopy->zones()->create([
                        'name' => $zone->name,
                        'polygon' => $zone->polygon,
                        'default_orientation' => $zone->default_orientation,
                        'reference_center' => $zone->reference_center,
                        'min_separation' => $zone->min_separation,
                        'priority' => $zone->priority,
                    ]);

                    foreach ($zone->slots as $slot) {
                        $zoneCopy->slots()->create([
                            'code' => $slot->code,
                            'stand_template_id' => $slot->stand_template_id,
                            'allowed_category_id' => $slot->allowed_category_id,
                            'image_position' => $slot->image_position,
                            'world_position' => $slot->world_position,
                            'rotation' => $slot->rotation,
                            'max_width' => $slot->max_width,
                            'max_depth' => $slot->max_depth,
                            'orientation_mode' => $slot->orientation_mode,
                            'accessible' => $slot->accessible,
                            'status' => 'disponible',
                            'source' => 'manual',
                        ]);
                    }
                }

                foreach ($plaza->props as $prop) {
                    $plazaCopy->props()->create([
                        'object_template_id' => $prop->object_template_id,
                        'image_position' => $prop->image_position,
                        'world_position' => $prop->world_position,
                        'rotation' => $prop->rotation,
                        'scale' => $prop->scale,
                        'scale_vector' => $prop->scale_vector,
                        'source' => 'manual',
                        'status' => 'borrador',
                    ]);
                }
            }

            return $copy;
        });
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
