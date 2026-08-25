<?php

namespace App\Domain\Immersive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Un anuncio (imagen) que puede mostrarse en la pantalla de un elemento de
 * plaza renderizable como billboard (`ImmersivePlazaProp` cuya plantilla
 * trae `screen_material_name`). Varios anuncios activos de la misma
 * colocación rotan en carrusel en la escena (ver
 * `public/js/lib/billboard-ad-utils.js`); `position` fija el orden del
 * carrusel. Vive por COLOCACIÓN (`immersive_plaza_prop_id`), no por
 * plantilla del catálogo: el mismo objeto "billboard" puesto en dos plazas
 * distintas puede anunciar cosas distintas en cada una.
 */
class ImmersiveBillboardAd extends Model
{
    protected $fillable = [
        'immersive_plaza_prop_id',
        'image_path',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ImmersivePlazaProp, $this>
     */
    public function prop(): BelongsTo
    {
        return $this->belongsTo(ImmersivePlazaProp::class, 'immersive_plaza_prop_id');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
