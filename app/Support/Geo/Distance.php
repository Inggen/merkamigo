<?php

namespace App\Support\Geo;

/**
 * Distancia entre dos coordenadas (fórmula de Haversine), en kilómetros.
 * Se calcula en PHP y no con funciones trigonométricas en SQL para no
 * acoplar la "cercanía" (1.1.1/1.5 del TODO) a un motor de base de datos
 * concreto — MySQL/MariaDB en producción, SQLite en pruebas.
 */
class Distance
{
    private const EARTH_RADIUS_KM = 6371.0;

    public static function kilometers(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $deltaLat = deg2rad($toLat - $fromLat);
        $deltaLng = deg2rad($toLng - $fromLng);

        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($deltaLng / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
