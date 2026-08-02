<?php

namespace App\Domain\Discovery\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Municipality extends Model
{
    protected $fillable = ['name', 'slug', 'department', 'cover_path', 'hero_video_path', 'cover_alt_text', 'latitude', 'longitude', 'is_active'];

    /**
     * Invalida el caché de `GET /api/v1/municipios` (5.1/5.3 del TODO) al
     * guardar o borrar desde Filament — la única forma en que cambia hoy.
     */
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('api.v1.municipios'));
        static::deleted(fn () => Cache::forget('api.v1.municipios'));
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * @return HasMany<Business, $this>
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function heroVideoUrl(): ?string
    {
        return $this->hero_video_path ? Storage::disk('public')->url($this->hero_video_path) : null;
    }

    /**
     * Fondo del hero de búsqueda/plaza para un municipio.
     *
     * Regla de negocio: SIEMPRE debe salir de lo configurado en admin para
     * ese municipio (`hero_video_path` o `cover_path`). Nunca se debe
     * reemplazar aquí por assets mock, fondos de labs 3D o imágenes fijas
     * por slug, salvo el fallback genérico cuando el municipio no tenga nada
     * configurado.
     *
     * @return array{type: 'image'|'video', url: string}|null
     */
    public function searchHeroMedia(): ?array
    {
        if ($this->heroVideoUrl()) {
            return ['type' => 'video', 'url' => $this->heroVideoUrl()];
        }

        if ($this->coverUrl()) {
            return ['type' => 'image', 'url' => $this->coverUrl()];
        }

        return null;
    }

    /**
     * Alias conservado por compatibilidad. El hero del buscador/plaza debe
     * seguir leyendo exactamente la misma configuración administrable del
     * municipio.
     *
     * @return array{type: 'image'|'video', url: string}|null
     */
    public function immersiveHeroMedia(): ?array
    {
        return $this->searchHeroMedia();
    }

    public function canAutoDetect(): bool
    {
        return filled($this->latitude) && filled($this->longitude);
    }

    public function immersiveLabUrl(): ?string
    {
        return match ($this->slug) {
            'zipaquira' => route('labs.zipa-inmersiva'),
            'cajica' => route('labs.cajica-inmersiva'),
            default => null,
        };
    }
}
