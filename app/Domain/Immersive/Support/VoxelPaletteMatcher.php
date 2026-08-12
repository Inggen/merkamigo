<?php

namespace App\Domain\Immersive\Support;

/**
 * Traduce colores hex arbitrarios (lo que un admin escribe en "Colores
 * permitidos") a nombres de textura del motor. El motor voxel
 * (`voxel-plaza-engine.js`) no admite colores hex libres: cada caja solo
 * puede pintarse con una de las texturas con nombre de
 * `VoxelDefinitionValidator::ALLOWED_TEXTURES`, cada una con un color FIJO
 * (`basePalette`/`createVoxelTextures()` en el motor). Sin esta traducción,
 * "Colores permitidos" se guardaba en la plantilla pero nunca llegaba a la
 * IA ni al schema — no tenía ningún efecto real.
 */
class VoxelPaletteMatcher
{
    /**
     * Colores base de cada textura permitida — debe mantenerse sincronizado
     * A MANO con `basePalette` en `public/js/lib/voxel-plaza-engine.js`,
     * mismo criterio ya usado para `VoxelDefinitionValidator::ALLOWED_TEXTURES`
     * (no hay forma de introspeccionar el JS desde PHP).
     *
     * @var array<string, int>
     */
    private const TEXTURE_HEX_COLORS = [
        'plaza' => 0xD3BB8B,
        'stone' => 0xD6C18D,
        'stoneLight' => 0xE5D3A8,
        'white' => 0xF4EBE2,
        'ochre' => 0xD69A43,
        'coral' => 0xC9754B,
        'butter' => 0xE2C36B,
        'roof' => 0xC56F3A,
        'roofClay' => 0xB76134,
        'wood' => 0x6D4B30,
        'woodDark' => 0x311B0C,
        'leaf' => 0x6F9D37,
        'mountain' => 0x617F4F,
        'glass' => 0x7BC0E9,
        'trim' => 0xA17C57,
        'iron' => 0x8493A4,
        'concrete' => 0xB5B7B9,
        'brick' => 0xAD5A3B,
        'water' => 0x73D2E5,
        'flower' => 0xDB5775,
        'cloth' => 0xC98E39,
        'skin' => 0xDFAA77,
        'shirt' => 0x2869D0,
        'pants' => 0x33445C,
        'grass' => 0x90B85E,
        'path' => 0xCAB07F,
        'patina' => 0x6F8F79,
        'accent' => 0xDF3527,
        'brickAccent' => 0xE6E0D3,
    ];

    /**
     * Para cada color hex pedido, devuelve el nombre de textura cuyo color
     * base es más cercano (distancia euclidiana en RGB). Un color mal
     * escrito no lanza excepción — simplemente no aporta ninguna textura a
     * la lista, en vez de romper la generación por un typo del admin.
     *
     * @param  array<int, string>  $hexColors
     * @return array<int, string> nombres de textura sin duplicados, en el orden en que se encontraron
     */
    public static function nearestTextures(array $hexColors): array
    {
        $textures = [];

        foreach ($hexColors as $hex) {
            $rgb = self::parseHex($hex);

            if ($rgb === null) {
                continue;
            }

            $closest = self::closestTexture($rgb);

            if (! in_array($closest, $textures, true)) {
                $textures[] = $closest;
            }
        }

        return $textures;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private static function closestTexture(array $rgb): string
    {
        // TEXTURE_HEX_COLORS nunca está vacío, así que el bucle siempre
        // asigna $closest al menos una vez.
        $closest = array_key_first(self::TEXTURE_HEX_COLORS);
        $closestDistance = null;

        foreach (self::TEXTURE_HEX_COLORS as $texture => $paletteHex) {
            $distance = self::distance($rgb, self::hexToRgb($paletteHex));

            if ($closestDistance === null || $distance < $closestDistance) {
                $closest = $texture;
                $closestDistance = $distance;
            }
        }

        return $closest;
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    private static function parseHex(string $hex): ?array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        return self::hexToRgb((int) hexdec($hex));
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hexToRgb(int $hex): array
    {
        return [($hex >> 16) & 0xFF, ($hex >> 8) & 0xFF, $hex & 0xFF];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $a
     * @param  array{0: int, 1: int, 2: int}  $b
     */
    private static function distance(array $a, array $b): float
    {
        return sqrt((($a[0] - $b[0]) ** 2) + (($a[1] - $b[1]) ** 2) + (($a[2] - $b[2]) ** 2));
    }
}
