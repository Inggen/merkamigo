<?php

namespace App\Domain\Immersive\Support\Exceptions;

use RuntimeException;

/**
 * IMM-020b: la IA no pudo generar/refinar una `model_definition` — sea
 * porque OpenAI está deshabilitado, la llamada HTTP falló, o la respuesta no
 * trae JSON interpretable. A diferencia de `OpenAiTextGenerator` (que cae en
 * silencio a un texto determinístico), aquí no existe un "objeto sin IA"
 * razonable, así que el fallo se propaga como excepción y la UI la muestra
 * como error sin perder la última definición válida en pantalla.
 */
class VoxelGenerationException extends RuntimeException {}
