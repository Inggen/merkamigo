<?php

namespace App\Http\Resources;

use App\Domain\Needs\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Offer
 */
class OfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'need_id' => $this->need_id,
            'business_id' => $this->business_id,
            'product_id' => $this->product_id,
            'message' => $this->message,
            'price' => $this->price,
            'availability' => $this->availability,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
