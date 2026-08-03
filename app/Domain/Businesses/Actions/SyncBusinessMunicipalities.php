<?php

namespace App\Domain\Businesses\Actions;

use App\Domain\Businesses\Models\Business;
use Illuminate\Validation\ValidationException;

/**
 * Sincroniza los municipios adicionales de un negocio (0.2.2 del TODO: una
 * vitrina puede estar en varios municipios) sin tocar el municipio
 * principal (`businesses.municipality_id`), que sigue siendo el único
 * campo que el resto del código asume como "el" municipio del negocio.
 */
class SyncBusinessMunicipalities
{
    /**
     * @param  array<int, int>  $municipalityIds
     */
    public function handle(Business $business, array $municipalityIds): void
    {
        $additional = collect($municipalityIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->reject(fn (int $id) => $id === (int) $business->municipality_id)
            ->values();

        if ($additional->count() > 3) {
            throw ValidationException::withMessages([
                'additional_municipality_ids' => __('Solo puedes seleccionar hasta 3 municipios adicionales.'),
            ]);
        }

        $business->municipalities()->sync($additional->all());
    }
}
