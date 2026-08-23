<?php

namespace App\Support\Ai\Contracts;

/**
 * Contrato interno para generación de imágenes con IA — mismo criterio
 * de desacople que `GeneratesAssistedText`, separado porque el modelo y
 * el endpoint de OpenAI son distintos (`image_model`/`/images/generations`
 * en vez de `model`/`/responses`).
 */
interface GeneratesImages
{
    /**
     * @param  array<string, mixed>  $options
     * @return string|null Contenido binario de la imagen generada, o null si no se pudo generar.
     */
    public function generate(string $prompt, array $options = []): ?string;
}
