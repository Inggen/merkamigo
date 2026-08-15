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
        'login_background_path',
        'footer_background_path',
        'apple_touch_icon_path',
        'main_search_background_path',
        'logo_path',
        'logo_mono_path',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create();
    }

    public function defaultShareImageUrl(): ?string
    {
        return $this->urlFor($this->default_share_image_path);
    }

    public function loginBackgroundUrl(): ?string
    {
        return $this->urlFor($this->login_background_path);
    }

    public function footerBackgroundUrl(): ?string
    {
        return $this->urlFor($this->footer_background_path);
    }

    public function appleTouchIconUrl(): ?string
    {
        return $this->urlFor($this->apple_touch_icon_path);
    }

    public function mainSearchBackgroundUrl(): ?string
    {
        return $this->urlFor($this->main_search_background_path);
    }

    public function logoUrl(): ?string
    {
        return $this->urlFor($this->logo_path);
    }

    public function logoMonoUrl(): ?string
    {
        return $this->urlFor($this->logo_mono_path);
    }

    private function urlFor(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
