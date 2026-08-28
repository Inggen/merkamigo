<?php

namespace App\Domain\Immersive\Support;

class StandardVoxelDefinitions
{
    /** @return array<string, mixed> */
    public static function marketStall(): array
    {
        $boxes = [
            self::box(0, 1.05, 0, 2.6, 1.1, 1.6, 'wood', 'base', collidable: true),
            self::box(0, 1.7, 0, 2.4, 0.2, 1.5, 'woodDark', 'base'),
            self::box(0, 3.1, 0, 0.18, 2.6, 0.18, 'iron', 'poste'),
        ];

        $canopyY = 4.4;
        $canopyRadius = 1.8;

        for ($index = 0; $index < 8; $index++) {
            $angle = (M_PI * 2 * $index) / 8;
            $nextAngle = (M_PI * 2 * ($index + 1)) / 8;
            $midAngle = ($angle + $nextAngle) / 2;

            $boxes[] = self::box(
                cos($midAngle) * $canopyRadius * 0.42,
                $canopyY + 0.18,
                sin($midAngle) * $canopyRadius * 0.42,
                $canopyRadius * 0.62,
                0.32,
                $canopyRadius * 0.62,
                $index % 2 === 0 ? 'color' : 'butter',
                'toldo',
                rotationY: $midAngle,
            );
        }

        $boxes[] = self::box(0, $canopyY + 0.5, 0, 0.5, 0.5, 0.5, 'color', 'toldo');

        return [
            'version' => 1,
            'groups' => [
                ['id' => 'base', 'name' => 'Base y mostrador'],
                ['id' => 'poste', 'name' => 'Poste'],
                ['id' => 'toldo', 'name' => 'Toldo'],
            ],
            'boxes' => $boxes,
        ];
    }

    /** @return array<string, mixed> */
    private static function box(
        float $x,
        float $y,
        float $z,
        float $width,
        float $height,
        float $depth,
        string $texture,
        string $groupId,
        bool $collidable = false,
        float $rotationY = 0,
    ): array {
        return [
            'x' => round($x, 6),
            'y' => round($y, 6),
            'z' => round($z, 6),
            'w' => round($width, 6),
            'h' => round($height, 6),
            'd' => round($depth, 6),
            'texture' => $texture,
            'rotationX' => 0,
            'rotationY' => round($rotationY, 6),
            'rotationZ' => 0,
            'collidable' => $collidable,
            'groupId' => $groupId,
        ];
    }
}
