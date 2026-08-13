<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Configuración general del sitio, editable desde el admin. Empieza con la
 * imagen genérica para compartir en redes (usada cuando una página no trae
 * su propia imagen de Open Graph) y crecerá con más ajustes de plataforma.
 */
class SiteSetting extends Model
{
    protected $table = 'site_settings';

    protected $fillable = [
        'default_share_image_path',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create();
    }

    public function defaultShareImageUrl(): ?string
    {
        return $this->default_share_image_path
            ? Storage::disk('public')->url($this->default_share_image_path)
            : null;
    }
}
