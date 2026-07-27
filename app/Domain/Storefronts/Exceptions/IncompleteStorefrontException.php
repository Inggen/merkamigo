<?php

namespace App\Domain\Storefronts\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando se intenta publicar una vitrina sin los datos mínimos
 * definidos en docs/product/alcance-fase0.md. Trae la lista de lo que
 * falta para que la UI la muestre tal como pide 1.2 del TODO ("mostrar
 * lista clara de información faltante").
 */
class IncompleteStorefrontException extends RuntimeException
{
    /**
     * @param  array<int, string>  $missing
     */
    public function __construct(public readonly array $missing)
    {
        parent::__construct('Faltan datos mínimos para publicar la vitrina.');
    }
}
