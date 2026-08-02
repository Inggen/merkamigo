<?php

namespace App\Http\Resources;

use App\Domain\Trust\Models\BusinessVerification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Nunca incluye `verification_document_path` (3.1 del TODO: "documentos
 * nunca son públicos") — solo el estado, no la ubicación del archivo.
 *
 * @mixin BusinessVerification
 */
class BusinessVerificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
