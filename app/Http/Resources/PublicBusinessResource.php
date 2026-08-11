<?php

namespace App\Http\Resources;

use App\Domain\Businesses\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
            'cover_url' => $this->storefront?->coverUrl(),
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
            // IMM-033: resumen de reseñas para el modal de vitrina de la
            // plaza inmersiva. No hay calificación numérica en este
            // codebase — las "reseñas" son texto libre (Recommendation).
            'recommendations_summary' => [
                'count' => $this->publishedRecommendations()->count(),
                'recent' => $this->publishedRecommendations()
                    ->sortByDesc('created_at')
                    ->take(3)
                    ->map(fn ($recommendation) => [
                        'body' => Str::limit($recommendation->body, 140),
                        'created_at' => $recommendation->created_at,
                    ])
                    ->values(),
            ],
            // IMM-034: en qué plaza inmersiva está este negocio (si tiene
            // un stand vivo asignado) y la URL para viajar ahí — el panel
            // de búsqueda de la plaza 3D lo usa para "buscar una vitrina
            // informa en qué plaza está y permite viajar a ella".
            'immersive_location' => $this->standAssignment?->isLive()
                ? [
                    'plaza_name' => $this->standAssignment->plaza->name,
                    'municipality_slug' => $this->standAssignment->plaza->experience->municipality->slug,
                    'travel_url' => $this->standAssignment->plaza->experience->municipality->immersiveLabUrl(),
                ]
                : null,
        ];
    }
}
