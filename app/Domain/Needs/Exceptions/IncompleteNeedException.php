<?php

namespace App\Domain\Needs\Exceptions;

use RuntimeException;

/**
 * Se lanza al intentar publicar una necesidad sin los datos mínimos de
 * 2.1 del TODO. Trae la lista de lo que falta, igual que
 * `IncompleteStorefrontException`.
 */
class IncompleteNeedException extends RuntimeException
{
    /**
     * @param  array<int, string>  $missing
     */
    public function __construct(public readonly array $missing)
    {
        parent::__construct('Faltan datos mínimos para publicar la solicitud.');
    }
}
