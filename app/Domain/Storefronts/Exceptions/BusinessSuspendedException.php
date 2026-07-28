<?php

namespace App\Domain\Storefronts\Exceptions;

use RuntimeException;

/**
 * Se lanza al intentar publicar un negocio suspendido por moderación
 * (1.9 del TODO): el propio emprendedor no puede revertir una suspensión
 * publicando de nuevo — solo un moderador puede restaurarla desde Filament.
 */
class BusinessSuspendedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este negocio está suspendido. Contacta a soporte para más información.');
    }
}
