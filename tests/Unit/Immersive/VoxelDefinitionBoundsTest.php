<?php

namespace Tests\Unit\Immersive;

use App\Domain\Immersive\Support\VoxelDefinitionBounds;
use PHPUnit\Framework\TestCase;

/**
 * IMM-020b del TODO inmersivo: bounding box de una `model_definition`
 * generada por IA, usado tanto por el validador como para completar
 * `max_width/max_depth/max_height` al guardar.
 */
class VoxelDefinitionBoundsTest extends TestCase
{
    public function test_single_box_without_rotation(): void
    {
        $bounds = VoxelDefinitionBounds::calculate([
            'boxes' => [
                ['x' => 0, 'y' => 1, 'z' => 0, 'w' => 2, 'h' => 2, 'd' => 4, 'texture' => 'wood'],
            ],
        ]);

        $this->assertEqualsWithDelta(2.0, $bounds['width'], 0.001);
        $this->assertEqualsWithDelta(4.0, $bounds['depth'], 0.001);
        $this->assertEqualsWithDelta(2.0, $bounds['height'], 0.001);
    }

    public function test_box_rotated_45_degrees_widens_the_xz_bounding_box(): void
    {
        $bounds = VoxelDefinitionBounds::calculate([
            'boxes' => [
                ['x' => 0, 'y' => 0.5, 'z' => 0, 'w' => 2, 'h' => 1, 'd' => 2, 'texture' => 'wood', 'rotationY' => M_PI / 4],
            ],
        ]);

        // Un cuadrado de 2x2 rotado 45° tiene una diagonal de 2*sqrt(2).
        $this->assertEqualsWithDelta(2 * sqrt(2), $bounds['width'], 0.01);
        $this->assertEqualsWithDelta(2 * sqrt(2), $bounds['depth'], 0.01);
    }

    public function test_box_rotated_90_degrees_swaps_width_and_depth(): void
    {
        $bounds = VoxelDefinitionBounds::calculate([
            'boxes' => [
                ['x' => 0, 'y' => 0.5, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 3, 'texture' => 'wood', 'rotationY' => M_PI / 2],
            ],
        ]);

        $this->assertEqualsWithDelta(3.0, $bounds['width'], 0.01);
        $this->assertEqualsWithDelta(1.0, $bounds['depth'], 0.01);
    }

    public function test_multiple_boxes_produce_the_union_bounding_box(): void
    {
        $bounds = VoxelDefinitionBounds::calculate([
            'boxes' => [
                ['x' => -2, 'y' => 0.5, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'wood'],
                ['x' => 2, 'y' => 2, 'z' => 0, 'w' => 1, 'h' => 3, 'd' => 1, 'texture' => 'iron'],
            ],
        ]);

        // De -2.5 a 2.5 en X: ancho 5. Altura máxima: 2 + 1.5 = 3.5 (desde el suelo).
        $this->assertEqualsWithDelta(5.0, $bounds['width'], 0.01);
        $this->assertEqualsWithDelta(3.5, $bounds['height'], 0.01);
    }

    public function test_empty_boxes_returns_zeroed_bounds(): void
    {
        $bounds = VoxelDefinitionBounds::calculate(['boxes' => []]);

        $this->assertSame(['width' => 0.0, 'depth' => 0.0, 'height' => 0.0], $bounds);
    }

    /**
     * Pedido del usuario: las cajas ya no restringen su rotación a Y — el
     * bounding box tiene que reflejar también rotationX/rotationZ. Una caja
     * de 1x1x2 acostada 90° sobre X queda "parada" en el otro sentido: su
     * alto pasa a ser la dimensión que antes era la profundidad.
     */
    public function test_box_rotated_90_degrees_on_x_swaps_height_and_depth(): void
    {
        $bounds = VoxelDefinitionBounds::calculate([
            'boxes' => [
                ['x' => 0, 'y' => 1, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 2, 'texture' => 'wood', 'rotationX' => M_PI / 2],
            ],
        ]);

        $this->assertEqualsWithDelta(1.0, $bounds['width'], 0.01);
        $this->assertEqualsWithDelta(1.0, $bounds['depth'], 0.01);
        // Centro en y=1, mitad de la dimensión larga (ahora vertical) = 1 →
        // desde el suelo: 1 (centro) + 1 (mitad) = 2.
        $this->assertEqualsWithDelta(2.0, $bounds['height'], 0.01);
    }

    /**
     * Sin rotationX/rotationZ en la caja (compatibilidad con definiciones
     * viejas que solo traen rotationY), el resultado no cambia frente al
     * mismo caso sin esas claves.
     */
    public function test_missing_rotation_x_and_z_default_to_zero(): void
    {
        $withKeys = VoxelDefinitionBounds::calculate([
            'boxes' => [
                ['x' => 0, 'y' => 0.5, 'z' => 0, 'w' => 2, 'h' => 1, 'd' => 2, 'texture' => 'wood', 'rotationX' => 0, 'rotationY' => M_PI / 4, 'rotationZ' => 0],
            ],
        ]);

        $withoutKeys = VoxelDefinitionBounds::calculate([
            'boxes' => [
                ['x' => 0, 'y' => 0.5, 'z' => 0, 'w' => 2, 'h' => 1, 'd' => 2, 'texture' => 'wood', 'rotationY' => M_PI / 4],
            ],
        ]);

        $this->assertEqualsWithDelta($withoutKeys['width'], $withKeys['width'], 0.0001);
        $this->assertEqualsWithDelta($withoutKeys['depth'], $withKeys['depth'], 0.0001);
        $this->assertEqualsWithDelta($withoutKeys['height'], $withKeys['height'], 0.0001);
    }
}
