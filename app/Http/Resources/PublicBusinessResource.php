<?php

namespace App\Http\Resources;

use App\Domain\Businesses\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vista pública de un negocio publicado (5.1 del TODO), pensada para
 * clientes externos de la API — solo campos ya visibles en la vitrina
 * pública (`vitrinas.show`), nunca datos internos del equipo (miembros,
 * plan, pagos).
 *
 * @mixin Business
 */
class PublicBusinessResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'whatsapp_number' => $this->whatsapp_number,
            'headline' => $this->storefront?->headline,
            'description' => $this->storefront?->description,
            'logo_url' => $this->logoUrl(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'municipality' => new MunicipalityResource($this->whenLoaded('municipality')),
            'zone' => $this->zone,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'distance_km' => $this->distance_km,
            'hours_note' => $this->hoursNote(),
            'is_open_now' => $this->isOpenNow(),
            'is_featured' => $this->isFeatured(),
            'has_verified_badge' => $this->hasVerifiedBadge(),
            'verified_badge_label' => $this->verifiedBadgeLabel(),
            'url' => route('vitrinas.show', $this->resource),
        ];
    }
}
