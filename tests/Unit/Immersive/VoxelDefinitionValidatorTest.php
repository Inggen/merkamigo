<?php

namespace Tests\Unit\Immersive;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Support\Exceptions\VoxelDefinitionValidationException;
use App\Domain\Immersive\Support\VoxelDefinitionValidator;
use PHPUnit\Framework\TestCase;

/**
 * IMM-020b del TODO inmersivo: puerta única antes de guardar/renderizar una
 * `model_definition` generada por IA. No usa `RefreshDatabase` — el
 * validador solo lee atributos de un `ImmersiveObjectTemplate` en memoria,
 * nunca consulta la base de datos.
 */
class VoxelDefinitionValidatorTest extends TestCase
{
    private function template(array $overrides = []): ImmersiveObjectTemplate
    {
        return new ImmersiveObjectTemplate(array_merge([
            'name' => 'Plantilla de prueba',
            'slug' => 'plantilla-de-prueba',
            'max_width' => 4.0,
            'max_depth' => 4.0,
            'max_height' => 3.0,
        ], $overrides));
    }

    private function validBox(array $overrides = []): array
    {
        return array_merge([
            'x' => 0, 'y' => 0.5, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'wood', 'rotationY' => 0, 'collidable' => false,
        ], $overrides);
    }

    public function test_it_accepts_a_valid_definition(): void
    {
        $bounds = (new VoxelDefinitionValidator)->validate([
            'version' => 1,
            'boxes' => [$this->validBox()],
        ], $this->template());

        $this->assertSame(1.0, $bounds['width']);
        $this->assertSame(1.0, $bounds['depth']);
    }

    public function test_it_rejects_a_texture_outside_the_allowlist(): void
    {
        $this->expectException(VoxelDefinitionValidationException::class);

        (new VoxelDefinitionValidator)->validate([
            'version' => 1,
            'boxes' => [$this->validBox(['texture' => 'neon-pink'])],
        ], $this->template());
    }

    public function test_it_rejects_non_positive_dimensions(): void
    {
        try {
            (new VoxelDefinitionValidator)->validate([
                'version' => 1,
                'boxes' => [$this->validBox(['w' => 0])],
            ], $this->template());

            $this->fail('Se esperaba VoxelDefinitionValidationException.');
        } catch (VoxelDefinitionValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
    }

    public function test_it_rejects_non_numeric_fields(): void
    {
        $this->expectException(VoxelDefinitionValidationException::class);

        (new VoxelDefinitionValidator)->validate([
            'version' => 1,
            'boxes' => [$this->validBox(['x' => 'no-es-un-numero'])],
        ], $this->template());
    }

    /**
     * JSON no puede codificar NaN/Infinity, pero un número fuera de rango
     * (ej. 1e400) sí desborda a INF como float de PHP — sin este chequeo
     * pasaría "es numérico" y rompería el cálculo de bounds silenciosamente.
     */
    public function test_it_rejects_a_box_field_that_overflows_to_infinity(): void
    {
        try {
            (new VoxelDefinitionValidator)->validate([
                'version' => 1,
                'boxes' => [$this->validBox(['w' => 1e400])],
            ], $this->template());

            $this->fail('Se esperaba VoxelDefinitionValidationException.');
        } catch (VoxelDefinitionValidationException $exception) {
            $this->assertStringContainsString('finito', implode(' ', $exception->errors()));
        }
    }

    public function test_it_rejects_a_non_finite_rotation(): void
    {
        $this->expectException(VoxelDefinitionValidationException::class);

        (new VoxelDefinitionValidator)->validate([
            'version' => 1,
            'boxes' => [$this->validBox(['rotationY' => -1e400])],
        ], $this->template());
    }

    public function test_it_rejects_more_boxes_than_the_template_configured_maximum(): void
    {
        $boxes = array_fill(0, 5, $this->validBox());

        $this->expectException(VoxelDefinitionValidationException::class);

        (new VoxelDefinitionValidator)->validate([
            'version' => 1,
            'boxes' => $boxes,
        ], $this->template(['max_boxes' => 4]));
    }

    /**
     * IMM-020b: `max_boxes` es por plantilla (una catedral necesita muchas
     * más cajas que un stand) — un objeto grande con un `max_boxes` alto
     * configurado no debe chocar contra un límite global bajo.
     */
    public function test_a_template_with_a_higher_max_boxes_allows_more_boxes(): void
    {
        $boxes = array_fill(0, 60, $this->validBox());

        $bounds = (new VoxelDefinitionValidator)->validate([
            'version' => 1,
            'boxes' => $boxes,
        ], $this->template(['max_boxes' => 100, 'max_width' => 4, 'max_depth' => 4, 'max_height' => 3]));

        $this->assertSame(1.0, $bounds['width']);
    }

    /**
     * El argumento del constructor solo sirve de respaldo cuando la
     * plantilla no trae `max_boxes` poblado (no debería pasar con la
     * columna ya en BD, pero cubre instancias construidas a mano).
     */
    public function test_the_constructor_default_is_used_when_the_template_has_no_max_boxes(): void
    {
        $template = $this->template();
        $template->max_boxes = null;

        $boxes = array_fill(0, 5, $this->validBox());

        $this->expectException(VoxelDefinitionValidationException::class);

        (new VoxelDefinitionValidator(maxBoxes: 4))->validate([
            'version' => 1,
            'boxes' => $boxes,
        ], $template);
    }

    public function test_it_rejects_a_bounding_box_larger_than_the_template_maximums(): void
    {
        $this->expectException(VoxelDefinitionValidationException::class);

        (new VoxelDefinitionValidator)->validate([
            'version' => 1,
            'boxes' => [$this->validBox(['w' => 10, 'd' => 10])],
        ], $this->template());
    }

    public function test_it_rejects_a_definition_with_the_wrong_version(): void
    {
        $this->expectException(VoxelDefinitionValidationException::class);

        (new VoxelDefinitionValidator)->validate([
            'version' => 2,
            'boxes' => [$this->validBox()],
        ], $this->template());
    }

    public function test_it_accumulates_multiple_errors_in_a_single_exception(): void
    {
        try {
            (new VoxelDefinitionValidator)->validate([
                'version' => 1,
                'boxes' => [$this->validBox(['texture' => 'not-allowed', 'w' => -1])],
            ], $this->template());

            $this->fail('Se esperaba VoxelDefinitionValidationException.');
        } catch (VoxelDefinitionValidationException $exception) {
            $this->assertGreaterThanOrEqual(2, count($exception->errors()));
        }
    }

    public function test_it_rejects_an_empty_boxes_list(): void
    {
        $this->expectException(VoxelDefinitionValidationException::class);

        (new VoxelDefinitionValidator)->validate([
            'version' => 1,
            'boxes' => [],
        ], $this->template());
    }
}
