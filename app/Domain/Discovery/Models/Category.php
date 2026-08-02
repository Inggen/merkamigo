<?php

namespace App\Domain\Discovery\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'position', 'is_active'];

    /**
     * Invalida el caché de `GET /api/v1/categorias` (5.1/5.3 del TODO) al
     * guardar o borrar desde Filament — la única forma en que cambia hoy.
     */
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('api.v1.categorias'));
        static::deleted(fn () => Cache::forget('api.v1.categorias'));
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @return HasMany<Business, $this>
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }
}
