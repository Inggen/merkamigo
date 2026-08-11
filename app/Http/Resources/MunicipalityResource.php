<?php

namespace App\Http\Resources;

use App\Domain\Discovery\Models\Municipality;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Municipality
 */
class MunicipalityResource extends JsonResource
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
            'department' => $this->department,
            'cover_url' => $this->coverUrl(),
            // IMM-034: null si el municipio no tiene experiencia inmersiva
            // publicada — el panel de "selector de plaza" de la plaza 3D
            // filtra por este campo para saber a dónde se puede viajar.
            'immersive_lab_url' => $this->immersiveLabUrl(),
        ];
    }
}
