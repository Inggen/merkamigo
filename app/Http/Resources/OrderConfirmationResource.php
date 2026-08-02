<?php

namespace App\Http\Resources;

use App\Domain\Trust\Models\OrderConfirmation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderConfirmation
 */
class OrderConfirmationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'status' => $this->status,
            'summary' => $this->summary,
            'customer_confirmed_at' => $this->customer_confirmed_at?->toIso8601String(),
            'business_confirmed_at' => $this->business_confirmed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_reputation_eligible' => $this->is_reputation_eligible,
        ];
    }
}
