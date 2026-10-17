<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Configuración general del sitio, editable desde el admin. Empieza con la
 * imagen genérica para compartir en redes (usada cuando una página no trae
 * su propia imagen de Open Graph) y crecerá con más ajustes de plataforma.
 *
 * Banners de página (2026-10-14, pedido del usuario): imagen + título +
 * descripción de Vitrinas, Eventos, Merkapuntos, Merkamigo Premia, Pídelo
 * y la bienvenida de "Crear vitrina" — todos `nullable`, cada vista cae
 * de vuelta a su copy/imagen original (`??`) si el admin no cargó nada.
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
        'meta_pixel_id',
        'create_vitrina_video_path',
        'pidelo_video_path',
        'social_facebook_url',
        'social_instagram_url',
        'social_tiktok_url',
        'social_whatsapp_url',
        'vitrinas_hero_title',
        'vitrinas_hero_description',
        'eventos_banner_path',
        'eventos_banner_title',
        'eventos_banner_description',
        'merkapuntos_banner_path',
        'merkapuntos_banner_title',
        'merkapuntos_banner_description',
        'premia_banner_path',
        'premia_banner_title',
        'premia_banner_description',
        'pidelo_banner_path',
        'pidelo_banner_title',
        'pidelo_banner_description',
        'bienvenida_banner_path',
        'bienvenida_banner_title',
        'bienvenida_banner_description',
    ];

    /**
     * Perfiles sociales reales con valor (sin los vacíos), para
     * `SchemaBuilder::organization()->sameAs` y el pie de página — nunca
     * se inventan, solo se usan si se cargaron desde Filament.
     *
     * @return array<int, string>
     */
    public function socialProfileUrls(): array
    {
        return array_values(array_filter([
            $this->social_facebook_url,
            $this->social_instagram_url,
            $this->social_tiktok_url,
            $this->social_whatsapp_url,
        ]));
    }

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

    public function createVitrinaVideoUrl(): ?string
    {
        return $this->urlFor($this->create_vitrina_video_path);
    }

    public function pideloVideoUrl(): ?string
    {
        return $this->urlFor($this->pidelo_video_path);
    }

    public function eventosBannerUrl(): ?string
    {
        return $this->urlFor($this->eventos_banner_path);
    }

    public function merkapuntosBannerUrl(): ?string
    {
        return $this->urlFor($this->merkapuntos_banner_path);
    }

    public function premiaBannerUrl(): ?string
    {
        return $this->urlFor($this->premia_banner_path);
    }

    public function pideloBannerUrl(): ?string
    {
        return $this->urlFor($this->pidelo_banner_path);
    }

    public function bienvenidaBannerUrl(): ?string
    {
        return $this->urlFor($this->bienvenida_banner_path);
    }

    private function urlFor(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
