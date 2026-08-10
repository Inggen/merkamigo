<?php

namespace App\Domain\Immersive\Support\Exceptions;

use RuntimeException;

/**
 * IMM-020b: una `model_definition` (generada por IA o refinada a mano) no
 * pasó `VoxelDefinitionValidator`. Acumula TODOS los problemas encontrados
 * para que el admin los vea de una sola vez, en vez de corregir uno y
 * volver a intentar.
 */
class VoxelDefinitionValidationException extends RuntimeException
{
    /**
     * @param  array<int, string>  $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('La definición del objeto no es válida: '.implode(' ', $errors));
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
