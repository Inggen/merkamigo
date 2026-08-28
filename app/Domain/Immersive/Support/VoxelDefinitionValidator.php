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
        'grass', 'path', 'patina', 'accent', 'brickAccent', 'color', 'collisionBarrier',
    ];

    /**
     * Pedido del usuario: un "tipo de objeto especial" estrictamente
     * colisionante (semitransparente azul claro, bordes en azul más
     * fuerte — ver `createVoxelTextures()`/`addCollisionBarrierEdges()` en
     * voxel-plaza-engine.js). Bloquear el paso es el único propósito de
     * esta textura, así que una caja que la use SIEMPRE debe ser
     * `collidable` — no queda a criterio del admin, se valida acá.
     */
    public const COLLISION_BARRIER_TEXTURE = 'collisionBarrier';

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

        $errors = [...$errors, ...$this->validateGroups($definition['groups'] ?? null, $boxes)];

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

        // Pedido del usuario: las cajas rotan libre en los 3 ejes (el gizmo
        // del editor ya no restringe X/Z) — `rotationX`/`rotationZ` son tan
        // opcionales como `rotationY` siempre lo fue (compatibilidad con
        // definiciones viejas que solo tienen esta última).
        foreach (['rotationX', 'rotationY', 'rotationZ'] as $field) {
            if (! array_key_exists($field, $box)) {
                continue;
            }

            if (! is_numeric($box[$field])) {
                $errors[] = "La caja #{$index}: \"{$field}\" debe ser numérico.";
            } elseif (! is_finite((float) $box[$field])) {
                $errors[] = "La caja #{$index}: \"{$field}\" debe ser un número finito.";
            }
        }

        if (array_key_exists('collidable', $box) && ! is_bool($box['collidable'])) {
            $errors[] = "La caja #{$index}: \"collidable\" debe ser verdadero o falso.";
        }

        if (($box['texture'] ?? null) === self::COLLISION_BARRIER_TEXTURE && ($box['collidable'] ?? false) !== true) {
            $errors[] = "La caja #{$index}: una caja con textura \"".self::COLLISION_BARRIER_TEXTURE.'" es estrictamente colisionante — "collidable" debe ser verdadero.';
        }

        if (array_key_exists('locked', $box) && ! is_bool($box['locked'])) {
            $errors[] = "La caja #{$index}: \"locked\" debe ser verdadero o falso.";
        }

        if (array_key_exists('tiling', $box) && $box['tiling'] !== null) {
            $tiling = $box['tiling'];
            $u = is_array($tiling) ? ($tiling['u'] ?? null) : null;
            $v = is_array($tiling) ? ($tiling['v'] ?? null) : null;

            if (! is_numeric($u) || ! is_numeric($v) || (float) $u <= 0 || (float) $v <= 0) {
                $errors[] = "La caja #{$index}: \"tiling\" debe traer \"u\" y \"v\" numéricos mayores que cero.";
            }
        }

        if (array_key_exists('groupId', $box) && $box['groupId'] !== null && (! is_string($box['groupId']) || $box['groupId'] === '')) {
            $errors[] = "La caja #{$index}: \"groupId\" debe ser un texto no vacío o nulo.";
        }

        // Pedido del usuario: dar la impresión de que una caja está
        // iluminada (ej. el bombillo de un farol) sin necesitar una luz
        // real en la escena — color emisivo opcional, `null` = apagado.
        if (array_key_exists('emissive', $box) && $box['emissive'] !== null
            && (! is_string($box['emissive']) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $box['emissive']))) {
            $errors[] = "La caja #{$index}: \"emissive\" debe ser un color hexadecimal (#rrggbb) o nulo.";
        }

        return $errors;
    }

    /**
     * Pedido del usuario: agrupar cajas del editor de objeto para mover/
     * rotar/escalar juntas con el gizmo. `groups` es una lista de
     * `{id, name}` a nivel de la definición; cada caja solo guarda el `id`
     * del grupo al que pertenece (`groupId`) — aquí se valida que ambos
     * lados calcen (ningún `groupId` de caja apunta a un grupo inexistente).
     *
     * @param  array<int, mixed>  $boxes
     * @return array<int, string>
     */
    private function validateGroups(mixed $groups, array $boxes): array
    {
        $groups ??= [];

        if (! is_array($groups)) {
            return ['"groups" debe ser una lista.'];
        }

        $errors = [];
        $ids = [];

        foreach ($groups as $groupIndex => $group) {
            if (! is_array($group) || ! is_string($group['id'] ?? null) || $group['id'] === '') {
                $errors[] = "El grupo #{$groupIndex}: \"id\" debe ser un texto no vacío.";

                continue;
            }

            if (! is_string($group['name'] ?? null) || trim($group['name']) === '') {
                $errors[] = "El grupo #{$groupIndex}: \"name\" debe ser un texto no vacío.";
            }

            if (in_array($group['id'], $ids, true)) {
                $errors[] = "El grupo #{$groupIndex}: \"id\" duplicado ({$group['id']}).";

                continue;
            }

            $ids[] = $group['id'];
        }

        foreach ($boxes as $boxIndex => $box) {
            $groupId = is_array($box) ? ($box['groupId'] ?? null) : null;

            if ($groupId !== null && is_string($groupId) && ! in_array($groupId, $ids, true)) {
                $errors[] = "La caja #{$boxIndex}: \"groupId\" ({$groupId}) no corresponde a ningún grupo declarado.";
            }
        }

        return $errors;
    }
}
