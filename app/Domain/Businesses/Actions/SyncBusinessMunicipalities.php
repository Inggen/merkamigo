<?php

namespace App\Domain\Businesses\Actions;

use App\Domain\Businesses\Models\Business;

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
        $additional = array_diff($municipalityIds, [$business->municipality_id]);

        $business->municipalities()->sync($additional);
    }
}
