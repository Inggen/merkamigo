<?php

namespace App\Http\Resources;

use App\Domain\Storefronts\Models\Product;
use App\Domain\Storefronts\Models\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
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
            'type' => $this->type,
            'description' => $this->description,
            'price' => $this->price,
            'price_type' => $this->price_type,
            'unit' => $this->unit,
            'promo_price' => $this->promo_price,
            'promo_label' => $this->promo_label,
            'has_active_promo' => $this->hasActivePromo(),
            'is_available' => $this->is_available,
            'photos' => $this->whenLoaded('media', fn () => $this->media->map(fn (ProductMedia $media) => $media->url())->all()),
        ];
    }
}
