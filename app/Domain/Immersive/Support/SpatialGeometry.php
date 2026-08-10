<?php

namespace App\Domain\Immersive\Support;

/**
 * Geometría 2D (plano XZ) para las validaciones de IMM-013: "ningún slot
 * válido sale del parque, bloquea una zona excluida o queda ocupado por un
 * elemento generado". Todo se calcula en coordenadas de mundo, nunca en
 * coordenadas de imagen.
 *
 * Simplificación deliberada de MVP: la contención/intersección
 * polígono-rectángulo y polígono-polígono se evalúa por vértices (¿los
 * puntos de una figura caen dentro de la otra?), no por intersección real
 * de segmentos. Esto cubre el caso común (una reserva rectangular pequeña
 * dentro/fuera de un polígono simple) pero puede no detectar que un borde
 * delgado atraviese la figura sin que ningún vértice quede dentro. Pendiente
 * documentado para cuando IMM-013 tenga el editor visual real.
 */
class SpatialGeometry
{
    /**
     * @param  array{minX: float, maxX: float, minZ: float, maxZ: float}  $bounds
     * @param  array{x: float, z: float, width: float, depth: float}  $rect
     */
    public static function boundsContainRectangle(array $bounds, array $rect): bool
    {
        $halfW = $rect['width'] / 2;
        $halfD = $rect['depth'] / 2;

        return ($rect['x'] - $halfW) >= $bounds['minX']
            && ($rect['x'] + $halfW) <= $bounds['maxX']
            && ($rect['z'] - $halfD) >= $bounds['minZ']
            && ($rect['z'] + $halfD) <= $bounds['maxZ'];
    }

    /**
     * @param  array{minX: float, maxX: float, minZ: float, maxZ: float}  $bounds
     * @param  array{points: array<int, array{x: float, z: float}>}  $polygon
     */
    public static function boundsContainPolygon(array $bounds, array $polygon): bool
    {
        foreach ($polygon['points'] as $point) {
            if ($point['x'] < $bounds['minX'] || $point['x'] > $bounds['maxX']
                || $point['z'] < $bounds['minZ'] || $point['z'] > $bounds['maxZ']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{points: array<int, array{x: float, z: float}>}  $polygon
     * @param  array{x: float, z: float, width: float, depth: float}  $rect
     */
    public static function polygonContainsRectangle(array $polygon, array $rect): bool
    {
        foreach (self::rectangleCorners($rect) as $corner) {
            if (! self::pointInPolygon($corner, $polygon)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Aproximación por vértices: hay intersección si algún vértice de una
     * figura cae dentro de la otra.
     *
     * @param  array{points: array<int, array{x: float, z: float}>}  $polygon
     * @param  array{x: float, z: float, width: float, depth: float}  $rect
     */
    public static function polygonIntersectsRectangle(array $polygon, array $rect): bool
    {
        foreach (self::rectangleCorners($rect) as $corner) {
            if (self::pointInPolygon($corner, $polygon)) {
                return true;
            }
        }

        $rectAsPolygon = ['points' => self::rectangleCorners($rect)];

        foreach ($polygon['points'] as $point) {
            if (self::pointInPolygon($point, $rectAsPolygon)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{points: array<int, array{x: float, z: float}>}  $a
     * @param  array{points: array<int, array{x: float, z: float}>}  $b
     */
    public static function polygonsIntersect(array $a, array $b): bool
    {
        foreach ($a['points'] as $point) {
            if (self::pointInPolygon($point, $b)) {
                return true;
            }
        }

        foreach ($b['points'] as $point) {
            if (self::pointInPolygon($point, $a)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Colisión entre dos huellas rectangulares con margen de seguridad
     * (§5: "debe existir una distancia mínima configurable entre stands").
     * Ignora rotación (usa el AABB) — conservador, no permisivo: un
     * rectángulo rotado nunca ocupa más que su AABB.
     *
     * @param  array{x: float, z: float, width: float, depth: float}  $a
     * @param  array{x: float, z: float, width: float, depth: float}  $b
     */
    public static function rectanglesOverlap(array $a, array $b, float $margin = 0): bool
    {
        $aHalfW = $a['width'] / 2 + $margin;
        $aHalfD = $a['depth'] / 2 + $margin;
        $bHalfW = $b['width'] / 2;
        $bHalfD = $b['depth'] / 2;

        return abs($a['x'] - $b['x']) < ($aHalfW + $bHalfW)
            && abs($a['z'] - $b['z']) < ($aHalfD + $bHalfD);
    }

    /**
     * @param  array{x: float, z: float, width: float, depth: float}  $rect
     * @return array<int, array{x: float, z: float}>
     */
    public static function rectangleCorners(array $rect): array
    {
        $halfW = $rect['width'] / 2;
        $halfD = $rect['depth'] / 2;

        return [
            ['x' => $rect['x'] - $halfW, 'z' => $rect['z'] - $halfD],
            ['x' => $rect['x'] + $halfW, 'z' => $rect['z'] - $halfD],
            ['x' => $rect['x'] + $halfW, 'z' => $rect['z'] + $halfD],
            ['x' => $rect['x'] - $halfW, 'z' => $rect['z'] + $halfD],
        ];
    }

    /**
     * Ray casting estándar: cuenta cruces de un rayo horizontal desde el
     * punto contra los bordes del polígono. Impar de cruces = dentro.
     *
     * @param  array{x: float, z: float}  $point
     * @param  array{points: array<int, array{x: float, z: float}>}  $polygon
     */
    public static function pointInPolygon(array $point, array $polygon): bool
    {
        $points = $polygon['points'];
        $count = count($points);
        $inside = false;

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = $points[$i]['x'];
            $zi = $points[$i]['z'];
            $xj = $points[$j]['x'];
            $zj = $points[$j]['z'];

            $intersects = (($zi > $point['z']) !== ($zj > $point['z']))
                && ($point['x'] < ($xj - $xi) * ($point['z'] - $zi) / ($zj - $zi) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
