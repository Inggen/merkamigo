<?php

namespace App\Support\Geo;

use App\Support\Geo\Contracts\GeocodesAddresses;

/**
 * Implementación nula del contrato de geocodificación (5.4 del TODO):
 * nunca resuelve una dirección automáticamente, mantiene el
 * comportamiento actual donde el emprendedor comparte sus coordenadas de
 * forma manual y opcional. Sirve de binding por defecto hasta que se
 * elija y configure un proveedor real.
 */
class ManualGeocoder implements GeocodesAddresses
{
    public function geocode(string $address): ?array
    {
        return null;
    }
}
