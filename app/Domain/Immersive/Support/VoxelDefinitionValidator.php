<?php

namespace App\Domain\Immersive\Support;

use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Support\Exceptions\VoxelDefinitionValidationException;

/**
 * IMM-020b: puerta única antes de guardar o renderizar una `model_definition`
 * generada por IA — nunca se persiste ni se le pasa a `buildFromDefinition`
 * un JSON que no haya pasado por aquí. PHP puro, sin librería de JSON Schema
 * (no hay ninguna instalada en el proyecto), siguiendo el mismo estilo de
 * validación manual que `SpatialGeometry`/`ColorBlobDetector`.
 */
class VoxelDefinitionValidator
{
    /**
     * Vocabulario de texturas que el motor realmente sabe pintar. Mantener
     * sincronizado A MANO con `createVoxelTextures()` en
     * `public/js/lib/voxel-plaza-engine.js` — no hay forma de introspeccionar
     * el JS desde PHP.
     *
     * @var array<int, string>
     */
    public const ALLOWED_TEXTURES = [
        'plaza', 'pavement', 'stone', 'stoneLight', 'white', 'ochre', 'coral', 'butter',
        'roof', 'roofClay', 'wood', 'woodDark', 'leaf', 'mountain', 'glass',
        'trim', 'iron', 'concrete', 'brick', 'water', 'flower', 'cloth', 'skin', 'shirt', 'pants',
        'grass', 'path', 'patina', 'accent', 'brickAccent',
    ];

    public function __construct(
        private readonly int $maxBoxes = 40,
    ) {}

    /**
     * @param  array<string, mixed>  $definition
     * @return array{width: float, depth: float, height: float} bounding box calculado
     *
     * @throws VoxelDefinitionValidationException
     */
    public function validate(array $definition, ImmersiveObjectTemplate $template): array
    {
        $errors = [];

        if (($definition['version'] ?? null) !== 1) {
            $errors[] = 'La definición debe declarar "version": 1.';
        }

        $boxes = $definition['boxes'] ?? null;

        if (! is_array($boxes) || $boxes === []) {
            $errors[] = 'La definición debe incluir al menos una caja en "boxes".';
            throw new VoxelDefinitionValidationException($errors);
        }

        // IMM-020b: el límite es por plantilla (`max_boxes`, configurable en
        // el formulario) — una catedral necesita muchas más cajas que un
        // stand simple. El valor del constructor solo es un respaldo para
        // plantillas sin ese campo poblado (no debería pasar con la columna
        // ya en BD, pero cubre instancias construidas a mano en memoria).
        $maxBoxes = $template->max_boxes ?? $this->maxBoxes;

        if (count($boxes) > $maxBoxes) {
            $errors[] = sprintf('La definición tiene %d cajas; el máximo permitido para esta plantilla es %d.', count($boxes), $maxBoxes);
        }

        foreach ($boxes as $index => $box) {
            $errors = [...$errors, ...$this->validateBox($index, $box)];
        }

        if ($errors !== []) {
            throw new VoxelDefinitionValidationException($errors);
        }

        $bounds = VoxelDefinitionBounds::calculate($definition);

        if ($template->max_width && $bounds['width'] > $template->max_width) {
            $errors[] = sprintf('El ancho generado (%.2fm) supera el máximo de la plantilla (%.2fm).', $bounds['width'], $template->max_width);
        }

        if ($template->max_depth && $bounds['depth'] > $template->max_depth) {
            $errors[] = sprintf('El fondo generado (%.2fm) supera el máximo de la plantilla (%.2fm).', $bounds['depth'], $template->max_depth);
        }

        if ($template->max_height && $bounds['height'] > $template->max_height) {
            $errors[] = sprintf('La altura generada (%.2fm) supera el máximo de la plantilla (%.2fm).', $bounds['height'], $template->max_height);
        }

        if ($errors !== []) {
            throw new VoxelDefinitionValidationException($errors);
        }

        return $bounds;
    }

    /**
     * @return array<int, string>
     */
    private function validateBox(int|string $index, mixed $box): array
    {
        if (! is_array($box)) {
            return ["La caja #{$index} no es un objeto válido."];
        }

        $errors = [];

        foreach (['x', 'y', 'z', 'w', 'h', 'd'] as $field) {
            if (! is_numeric($box[$field] ?? null)) {
                $errors[] = "La caja #{$index}: \"{$field}\" debe ser numérico.";
            } elseif (! is_finite((float) $box[$field])) {
                // JSON no puede codificar NaN/Infinity directamente, pero un
                // número fuera de rango (ej. 1e400) sí desborda a INF como
                // float de PHP — sin este chequeo, una caja así pasaría
                // "es numérico" y rompería silenciosamente el cálculo de
                // bounds (VoxelDefinitionBounds) y el renderizado en Three.js.
                $errors[] = "La caja #{$index}: \"{$field}\" debe ser un número finito.";
            }
        }

        foreach (['w', 'h', 'd'] as $field) {
            if (is_numeric($box[$field] ?? null) && (float) $box[$field] <= 0) {
                $errors[] = "La caja #{$index}: \"{$field}\" debe ser mayor que cero.";
            }
        }

        $texture = $box['texture'] ?? null;

        if (! is_string($texture) || ! in_array($texture, self::ALLOWED_TEXTURES, true)) {
            $errors[] = "La caja #{$index}: \"texture\" debe ser una de: ".implode(', ', self::ALLOWED_TEXTURES).'.';
        }

        if (array_key_exists('rotationY', $box)) {
            if (! is_numeric($box['rotationY'])) {
                $errors[] = "La caja #{$index}: \"rotationY\" debe ser numérico.";
            } elseif (! is_finite((float) $box['rotationY'])) {
                $errors[] = "La caja #{$index}: \"rotationY\" debe ser un número finito.";
            }
        }

        if (array_key_exists('collidable', $box) && ! is_bool($box['collidable'])) {
            $errors[] = "La caja #{$index}: \"collidable\" debe ser verdadero o falso.";
        }

        return $errors;
    }
}
