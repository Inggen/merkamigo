<?php

namespace App\Http\Resources;

use App\Domain\Needs\Models\Need;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Catálogo público de necesidades abiertas (5.1 del TODO) — mismo criterio
 * de visibilidad que `Need::scopeOpenIn()` ya muestra públicamente en
 * `plaza.show` y a cualquier negocio en "Oportunidades": cualquiera puede
 * ver una necesidad abierta para poder responderle, no hay datos privados
 * del comprador más allá de lo que el propio `Need` ya guarda.
 *
 * @mixin Need
 */
class PublicNeedResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'budget' => $this->budget,
            'zone' => $this->zone,
            'municipality_id' => $this->municipality_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'published_at' => $this->published_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'offers_count' => $this->whenCounted('offers'),
        ];
    }
}
