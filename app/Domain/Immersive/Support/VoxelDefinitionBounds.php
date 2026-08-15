<?php

namespace App\Domain\Immersive\Support;

/**
 * IMM-020b: calcula la huella (`max_width`/`max_depth`/`max_height`) de una
 * `model_definition` generada por IA, a partir de sus cajas — mismo cálculo
 * que usa el validador antes de aceptar la definición y que se persiste al
 * guardarla. Coordenadas locales al grupo, suelo en y=0 (igual que todos los
 * builders de `voxel-plaza-engine.js`).
 *
 * Pedido del usuario: las cajas ya no restringen su rotación al eje Y — el
 * gizmo del editor las rota libremente en X/Y/Z. El bounding box, entonces,
 * ya no puede tratar cada caja como un rectángulo 2D girado sobre Y (lo que
 * bastaba antes); calcula las 8 esquinas de cada caja en 3D y las rota con
 * la MISMA matriz que Three.js aplica a `Object3D.rotation` (orden de Euler
 * 'XYZ' por defecto, ver `rotationMatrixXYZ()`) para que este cálculo
 * coincida con lo que realmente se ve en el visor.
 */
class VoxelDefinitionBounds
{
    /**
     * @param  array<string, mixed>  $definition
     * @return array{width: float, depth: float, height: float}
     */
    public static function calculate(array $definition): array
    {
        $boxes = $definition['boxes'] ?? [];

        if ($boxes === []) {
            return ['width' => 0.0, 'depth' => 0.0, 'height' => 0.0];
        }

        $minX = $minY = $minZ = INF;
        $maxX = $maxY = $maxZ = -INF;

        foreach ($boxes as $box) {
            foreach (self::rotatedCorners($box) as $corner) {
                $minX = min($minX, $corner['x']);
                $maxX = max($maxX, $corner['x']);
                $minY = min($minY, $corner['y']);
                $maxY = max($maxY, $corner['y']);
                $minZ = min($minZ, $corner['z']);
                $maxZ = max($maxZ, $corner['z']);
            }
        }

        return [
            'width' => $maxX - $minX,
            'depth' => $maxZ - $minZ,
            // El objeto se apoya en y=0 (igual que los builders existentes):
            // la altura total va desde el suelo, no desde la caja más baja.
            'height' => max($maxY, 0.0) - min($minY, 0.0),
        ];
    }

    /**
     * Las 8 esquinas de una caja, rotadas por sus `rotationX`/`rotationY`/
     * `rotationZ` (radianes, 0 si no vienen) y trasladadas a su posición.
     *
     * @param  array<string, mixed>  $box
     * @return array<int, array{x: float, y: float, z: float}>
     */
    private static function rotatedCorners(array $box): array
    {
        $x = (float) $box['x'];
        $y = (float) $box['y'];
        $z = (float) $box['z'];
        $halfW = ((float) $box['w']) / 2;
        $halfH = ((float) $box['h']) / 2;
        $halfD = ((float) $box['d']) / 2;

        $matrix = self::rotationMatrixXYZ(
            (float) ($box['rotationX'] ?? 0),
            (float) ($box['rotationY'] ?? 0),
            (float) ($box['rotationZ'] ?? 0),
        );

        $corners = [];

        foreach ([-1, 1] as $signX) {
            foreach ([-1, 1] as $signY) {
                foreach ([-1, 1] as $signZ) {
                    $localX = $signX * $halfW;
                    $localY = $signY * $halfH;
                    $localZ = $signZ * $halfD;

                    $corners[] = [
                        'x' => $x + $matrix[0][0] * $localX + $matrix[0][1] * $localY + $matrix[0][2] * $localZ,
                        'y' => $y + $matrix[1][0] * $localX + $matrix[1][1] * $localY + $matrix[1][2] * $localZ,
                        'z' => $z + $matrix[2][0] * $localX + $matrix[2][1] * $localY + $matrix[2][2] * $localZ,
                    ];
                }
            }
        }

        return $corners;
    }

    /**
     * Matriz de rotación equivalente a `Object3D.rotation.set(rx, ry, rz)`
     * de Three.js con su orden de Euler por defecto ('XYZ', que compone
     * como M = Rx · Ry · Rz) — es la misma rotación que aplica
     * `addVoxelBox()` en `voxel-plaza-engine.js` y el gizmo de rotación del
     * editor, así que el bounding box calculado aquí coincide con el
     * recuadro que se ve en el visor.
     *
     * @return array<int, array<int, float>>
     */
    private static function rotationMatrixXYZ(float $rotationX, float $rotationY, float $rotationZ): array
    {
        $c1 = cos($rotationX);
        $s1 = sin($rotationX);
        $c2 = cos($rotationY);
        $s2 = sin($rotationY);
        $c3 = cos($rotationZ);
        $s3 = sin($rotationZ);

        return [
            [$c2 * $c3, -$c2 * $s3, $s2],
            [$c1 * $s3 + $s1 * $s2 * $c3, $c1 * $c3 - $s1 * $s2 * $s3, -$s1 * $c2],
            [$s1 * $s3 - $c1 * $s2 * $c3, $s1 * $c3 + $c1 * $s2 * $s3, $c1 * $c2],
        ];
    }
}
