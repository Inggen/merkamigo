<?php

namespace App\Support\Geo\Contracts;

/**
 * Contrato interno de geocodificación (5.4 del TODO): convertir una
 * dirección de texto libre en coordenadas, sin acoplar el resto de la
 * app a un proveedor concreto (Google Maps, Nominatim...). Hoy no hay
 * proveedor elegido — `ManualGeocoder` es la única implementación, y
 * conserva el comportamiento actual (el emprendedor comparte sus
 * coordenadas manualmente). Cuando se elija un proveedor real, solo hace
 * falta una nueva implementación de esta interfaz y cambiar el binding.
 */
interface GeocodesAddresses
{
    /**
     * @return array{lat: float, lng: float}|null null si no se pudo geocodificar.
     */
    public function geocode(string $address): ?array;
}
