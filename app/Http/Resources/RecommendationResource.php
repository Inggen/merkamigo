<?php

namespace App\Http\Resources;

use App\Domain\Trust\Models\Recommendation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Recommendation
 */
class RecommendationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'tags' => $this->tags,
            'status' => $this->status,
            'business_response' => $this->business_response,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
