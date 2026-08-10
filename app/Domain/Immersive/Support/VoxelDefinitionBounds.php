<?php

namespace App\Domain\Immersive\Support;

/**
 * IMM-020b: calcula la huella (`max_width`/`max_depth`/`max_height`) de una
 * `model_definition` generada por IA, a partir de sus cajas — mismo cálculo
 * que usa el validador antes de aceptar la definición y que se persiste al
 * guardarla. Coordenadas locales al grupo, suelo en y=0 (igual que todos los
 * builders de `voxel-plaza-engine.js`).
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

        $minX = $minZ = $minY = INF;
        $maxX = $maxZ = $maxY = -INF;

        foreach ($boxes as $box) {
            foreach (self::rotatedCorners($box) as $corner) {
                $minX = min($minX, $corner['x']);
                $maxX = max($maxX, $corner['x']);
                $minZ = min($minZ, $corner['z']);
                $maxZ = max($maxZ, $corner['z']);
            }

            $halfH = ((float) $box['h']) / 2;
            $minY = min($minY, ((float) $box['y']) - $halfH);
            $maxY = max($maxY, ((float) $box['y']) + $halfH);
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
     * @param  array<string, mixed>  $box
     * @return array<int, array{x: float, z: float}>
     */
    private static function rotatedCorners(array $box): array
    {
        $x = (float) $box['x'];
        $z = (float) $box['z'];
        $halfW = ((float) $box['w']) / 2;
        $halfD = ((float) $box['d']) / 2;
        $angle = (float) ($box['rotationY'] ?? 0);

        $cos = cos($angle);
        $sin = sin($angle);

        $localCorners = [
            [-$halfW, -$halfD],
            [$halfW, -$halfD],
            [$halfW, $halfD],
            [-$halfW, $halfD],
        ];

        return array_map(static fn (array $corner): array => [
            'x' => $x + ($corner[0] * $cos - $corner[1] * $sin),
            'z' => $z + ($corner[0] * $sin + $corner[1] * $cos),
        ], $localCorners);
    }
}
