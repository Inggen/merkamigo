<?php

namespace App\Http\Resources;

use App\Domain\WhatsApp\Models\WhatsAppContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WhatsAppContent
 */
class WhatsAppContentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'tone' => $this->tone,
            'content' => $this->content,
            'scheduled_for' => $this->scheduled_for?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
