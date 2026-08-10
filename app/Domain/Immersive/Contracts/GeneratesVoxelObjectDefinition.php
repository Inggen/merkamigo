<?php

namespace App\Domain\Immersive\Contracts;

use App\Domain\Immersive\Support\Exceptions\VoxelGenerationException;

/**
 * IMM-020b: genera (o refina) una `model_definition` — el JSON de cajas
 * voxel que `buildFromDefinition` sabe renderizar — a partir de fotos de
 * referencia de un objeto físico. Contrato separado de
 * `GeneratesAssistedText` porque la forma de entrada (imágenes + JSON
 * estructurado) y la política de fallo (sin fallback silencioso) son
 * distintas.
 */
interface GeneratesVoxelObjectDefinition
{
    /**
     * @param  array<string, string>  $imagePaths  Rutas relativas en el disco 'public', indexadas por vista
     *                                             ('front'/'side'/'top'/'preview'; cualquier otra clave se trata
     *                                             como referencia genérica adicional). No hace falta traer las
     *                                             cuatro — el implementador debe funcionar con cualquier subconjunto.
     * @param  array<string, mixed>  $context  Nombre/categoría/huella máxima de la plantilla, para que la IA respete los límites.
     * @param  array<string, mixed>|null  $previousDefinition  Definición previa, si esto es un refinamiento sobre ella.
     * @return array<string, mixed> El `model_definition` decodificado (sin validar todavía — el llamador debe pasarlo por `VoxelDefinitionValidator`).
     *
     * @throws VoxelGenerationException
     */
    public function generate(
        array $imagePaths,
        string $instructions,
        array $context = [],
        ?array $previousDefinition = null,
    ): array;
}
