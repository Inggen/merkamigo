<?php

namespace App\Http\Resources;

use App\Domain\Storefronts\Models\Storefront;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Storefront
 */
class StorefrontResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'headline' => $this->headline,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
