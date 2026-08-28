<?php

namespace Tests\Unit\Immersive;

use App\Domain\Immersive\Support\VoxelDefinitionValidator;
use App\Domain\Immersive\Support\VoxelPaletteMatcher;
use PHPUnit\Framework\TestCase;

/**
 * El motor voxel no admite colores hex libres — "Colores permitidos" solo
 * puede tener efecto si se traduce a nombres de textura reales. Este test
 * protege esa traducción, no un color exacto: los colores en
 * `VoxelPaletteMatcher::TEXTURE_HEX_COLORS` deben mantenerse sincronizados a
 * mano con `basePalette` (`voxel-plaza-engine.js`), así que si ese archivo
 * cambia sin actualizar aquí, este test no lo detecta — es responsabilidad
 * del desarrollador mantenerlos alineados.
 */
class VoxelPaletteMatcherTest extends TestCase
{
    public function test_it_matches_a_color_to_its_own_exact_texture(): void
    {
        // 0x6d4b30 es exactamente el color de 'wood' en basePalette.
        $this->assertSame(['wood'], VoxelPaletteMatcher::nearestTextures(['#6d4b30']));
    }

    public function test_it_matches_a_slightly_off_color_to_the_closest_texture(): void
    {
        // Un poco más claro que 'wood' (#6d4b30) pero sigue siendo el más
        // cercano frente al resto de la paleta (tonos muy distintos).
        $this->assertSame(['wood'], VoxelPaletteMatcher::nearestTextures(['#725030']));
    }

    public function test_it_matches_the_pavement_gray_to_the_new_pavement_texture(): void
    {
        $this->assertSame(['pavement'], VoxelPaletteMatcher::nearestTextures(['#7a746b']));
    }

    public function test_it_deduplicates_colors_that_resolve_to_the_same_texture(): void
    {
        $result = VoxelPaletteMatcher::nearestTextures(['#6d4b30', '#6e4c31']);

        $this->assertSame(['wood'], $result);
    }

    public function test_it_ignores_unparseable_colors_without_throwing(): void
    {
        $this->assertSame([], VoxelPaletteMatcher::nearestTextures(['no-es-un-color', '', '#zzzzzz']));
    }

    public function test_it_supports_the_short_three_digit_hex_form(): void
    {
        // #fff -> #ffffff, más cercano a 'white' (0xf4ebe2) que a cualquier
        // otra textura de la paleta.
        $this->assertSame(['white'], VoxelPaletteMatcher::nearestTextures(['#fff']));
    }

    public function test_every_resolved_texture_is_part_of_the_allowed_vocabulary(): void
    {
        $result = VoxelPaletteMatcher::nearestTextures(['#6d4b30', '#f4ebe2', '#df3527']);

        foreach ($result as $texture) {
            $this->assertContains($texture, VoxelDefinitionValidator::ALLOWED_TEXTURES);
        }
    }

    public function test_it_returns_an_empty_list_for_an_empty_input(): void
    {
        $this->assertSame([], VoxelPaletteMatcher::nearestTextures([]));
    }

    public function test_personalizable_stand_color_is_an_allowed_voxel_texture(): void
    {
        $this->assertContains('color', VoxelDefinitionValidator::ALLOWED_TEXTURES);
    }
}
