<?php

namespace App\Domain\Immersive\Support;

use RuntimeException;

/**
 * IMM-013 del TODO inmersivo (redefinido): detección de manchas de color
 * sobre las dos imágenes que sube el administrador — la leyenda (colores
 * distintos → tipo de objeto) y el plano (dónde va cada uno). Usa GD
 * (incluido en PHP, sin dependencias nuevas): muestrea la imagen en una
 * cuadrícula, agrupa píxeles del mismo color por adyacencia (flood fill) y
 * devuelve el centroide de cada mancha.
 *
 * Deliberadamente NO hace OCR ni interpreta texto — el color se detecta
 * solo; qué significa cada color lo confirma el administrador (ver
 * `PlazaLegendEntry`). Tampoco detecta la orientación de las flechas
 * dibujadas junto a cada stand en el plano: es frágil de leer por píxeles
 * y no hace falta, porque la orientación ya sale de la regla de la zona
 * (`StandZone::default_orientation`).
 *
 * Limitación conocida: si dos categorías de la leyenda usan colores casi
 * idénticos (ej. "Pileta grande" y "Pileta pequeña" en el mismo tono de
 * cian), el detector las funde en una sola entrada — el color es la única
 * señal que usa, no la forma ni el tamaño del ícono.
 */
class ColorBlobDetector
{
    public function __construct(
        private readonly int $step = 3,
        private readonly int $colorTolerance = 28,
        private readonly int $minBlobCells = 4,
        private readonly int $transparencyThreshold = 100,
    ) {}

    /**
     * Colores distintos y significativos de una imagen de leyenda: agrupa
     * píxeles similares, descarta el color más frecuente (el fondo de la
     * página) y los casi negros (texto/bordes).
     *
     * @return array<int, array{color_hex: string, pixel_count: int, sample_x: int, sample_y: int}>
     */
    public function detectDistinctColors(string $absoluteImagePath): array
    {
        [$image, $width, $height] = $this->load($absoluteImagePath);

        $clusters = [];

        for ($y = 0; $y < $height; $y += $this->step) {
            for ($x = 0; $x < $width; $x += $this->step) {
                $rgb = $this->pixelAt($image, $x, $y);

                if ($rgb === null) {
                    continue;
                }

                $clusters = $this->accumulate($clusters, $rgb, $x, $y);
            }
        }

        imagedestroy($image);

        usort($clusters, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        // El color con más muestras casi siempre es el fondo de la página.
        array_shift($clusters);

        $results = [];

        foreach ($clusters as $cluster) {
            if ($cluster['count'] < $this->minBlobCells) {
                continue;
            }

            $rgb = [(int) round($cluster['r']), (int) round($cluster['g']), (int) round($cluster['b'])];

            if ($this->isNearBlack($rgb)) {
                continue;
            }

            $results[] = [
                'color_hex' => $this->toHex($rgb),
                'pixel_count' => $cluster['count'],
                'sample_x' => (int) $cluster['sampleX'],
                'sample_y' => (int) $cluster['sampleY'],
            ];
        }

        return $results;
    }

    /**
     * Manchas de colores conocidos (ya confirmados en la leyenda) sobre la
     * imagen del plano, con su centroide en coordenadas de imagen.
     *
     * @param  array<int, string>  $targetColorHexes
     * @return array<int, array{color_hex: string, x: float, y: float, pixel_count: int}>
     */
    public function detectBlobsForColors(string $absoluteImagePath, array $targetColorHexes): array
    {
        [$image, $width, $height] = $this->load($absoluteImagePath);

        $targets = array_map(fn (string $hex): array => $this->fromHex($hex), $targetColorHexes);
        $cols = intdiv($width, $this->step);
        $rows = intdiv($height, $this->step);

        $grid = [];

        for ($gy = 0; $gy < $rows; $gy++) {
            for ($gx = 0; $gx < $cols; $gx++) {
                $rgb = $this->pixelAt($image, $gx * $this->step, $gy * $this->step);
                $grid[$gy][$gx] = $rgb === null ? null : $this->matchTarget($rgb, $targetColorHexes, $targets);
            }
        }

        imagedestroy($image);

        $visited = [];
        $blobs = [];

        for ($gy = 0; $gy < $rows; $gy++) {
            for ($gx = 0; $gx < $cols; $gx++) {
                if (! empty($visited[$gy][$gx]) || ($grid[$gy][$gx] ?? null) === null) {
                    continue;
                }

                $hex = $grid[$gy][$gx];
                $component = $this->floodFill($grid, $visited, $gx, $gy, $hex, $cols, $rows);

                if (count($component) < $this->minBlobCells) {
                    continue;
                }

                $sumX = array_sum(array_column($component, 0));
                $sumY = array_sum(array_column($component, 1));
                $count = count($component);

                $blobs[] = [
                    'color_hex' => $hex,
                    'x' => ($sumX / $count) * $this->step,
                    'y' => ($sumY / $count) * $this->step,
                    'pixel_count' => $count,
                ];
            }
        }

        return $blobs;
    }

    /**
     * @param  array<int, array<int, string|null>>  $grid
     * @param  array<int, array<int, bool>>  $visited
     * @return array<int, array{0: int, 1: int}>
     */
    private function floodFill(array $grid, array &$visited, int $startX, int $startY, string $hex, int $cols, int $rows): array
    {
        $queue = [[$startX, $startY]];
        $visited[$startY][$startX] = true;
        $component = [];

        while ($queue) {
            [$cx, $cy] = array_pop($queue);
            $component[] = [$cx, $cy];

            foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
                $nx = $cx + $dx;
                $ny = $cy + $dy;

                if ($nx < 0 || $ny < 0 || $nx >= $cols || $ny >= $rows) {
                    continue;
                }

                if (! empty($visited[$ny][$nx]) || ($grid[$ny][$nx] ?? null) !== $hex) {
                    continue;
                }

                $visited[$ny][$nx] = true;
                $queue[] = [$nx, $ny];
            }
        }

        return $component;
    }

    /**
     * @param  array<int, array{r: float, g: float, b: float, count: int, sampleX: int, sampleY: int}>  $clusters
     * @param  array{0: int, 1: int, 2: int}  $rgb
     * @return array<int, array{r: float, g: float, b: float, count: int, sampleX: int, sampleY: int}>
     */
    private function accumulate(array $clusters, array $rgb, int $x, int $y): array
    {
        foreach ($clusters as $index => $cluster) {
            if ($this->colorDistance($rgb, [$cluster['r'], $cluster['g'], $cluster['b']]) <= $this->colorTolerance) {
                $n = $cluster['count'];
                $clusters[$index]['r'] = ($cluster['r'] * $n + $rgb[0]) / ($n + 1);
                $clusters[$index]['g'] = ($cluster['g'] * $n + $rgb[1]) / ($n + 1);
                $clusters[$index]['b'] = ($cluster['b'] * $n + $rgb[2]) / ($n + 1);
                $clusters[$index]['count'] = $n + 1;

                return $clusters;
            }
        }

        $clusters[] = ['r' => (float) $rgb[0], 'g' => (float) $rgb[1], 'b' => (float) $rgb[2], 'count' => 1, 'sampleX' => $x, 'sampleY' => $y];

        return $clusters;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     * @param  array<int, string>  $hexes
     * @param  array<int, array{0: int, 1: int, 2: int}>  $targets
     */
    private function matchTarget(array $rgb, array $hexes, array $targets): ?string
    {
        foreach ($targets as $index => $target) {
            if ($this->colorDistance($rgb, $target) <= $this->colorTolerance) {
                return $hexes[$index];
            }
        }

        return null;
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null null si el píxel es
     *                                            prácticamente transparente.
     */
    private function pixelAt(\GdImage $image, int $x, int $y): ?array
    {
        $index = imagecolorat($image, $x, $y);

        if ($index === false) {
            return null;
        }

        $rgba = imagecolorsforindex($image, $index);

        if ($rgba['alpha'] >= $this->transparencyThreshold) {
            return null;
        }

        return [$rgba['red'], $rgba['green'], $rgba['blue']];
    }

    /**
     * @return array{0: \GdImage, 1: int, 2: int}
     */
    private function load(string $path): array
    {
        $data = @file_get_contents($path);

        if ($data === false) {
            throw new RuntimeException("No se pudo leer la imagen: {$path}");
        }

        $image = @imagecreatefromstring($data);

        if ($image === false) {
            throw new RuntimeException("Formato de imagen no soportado: {$path}");
        }

        return [$image, imagesx($image), imagesy($image)];
    }

    /**
     * @param  array{0: int|float, 1: int|float, 2: int|float}  $a
     * @param  array{0: int|float, 1: int|float, 2: int|float}  $b
     */
    private function colorDistance(array $a, array $b): float
    {
        return sqrt((($a[0] - $b[0]) ** 2) + (($a[1] - $b[1]) ** 2) + (($a[2] - $b[2]) ** 2));
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function isNearBlack(array $rgb): bool
    {
        return $this->colorDistance($rgb, [0, 0, 0]) <= $this->colorTolerance;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function toHex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function fromHex(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
