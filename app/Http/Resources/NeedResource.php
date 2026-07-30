<?php

namespace App\Http\Resources;

use App\Domain\Needs\Models\Need;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Need
 */
class NeedResource extends JsonResource
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
            'municipality_id' => $this->municipality_id,
            'category_id' => $this->category_id,
            'zone' => $this->zone,
            'budget' => $this->budget,
            'status' => $this->status,
            'outcome' => $this->outcome,
            'published_at' => $this->published_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
