<?php

namespace App\Domain\Immersive\Support\Exceptions;

/**
 * Subtipo de `VoxelGenerationException` específico para el caso en que la
 * llamada a OpenAI se agotó por tiempo (`ConnectionException` del cliente
 * HTTP) — la única causa de fallo que `OpenAiVoxelObjectGenerator` puede
 * atribuir con confianza a "la complejidad pedida era demasiada para el
 * tiempo disponible", así que es la única que dispara un reintento
 * automático con un `maxBoxes` más conservador. Cualquier otro fallo (auth,
 * red caída, JSON inválido, HTTP 4xx/5xx) sigue siendo un
 * `VoxelGenerationException` normal, sin reintento.
 */
class VoxelGenerationTimeoutException extends VoxelGenerationException {}
