<?php

namespace App\Domain\Needs\Exceptions;

use RuntimeException;

/**
 * Se lanza al intentar enviar una propuesta a una necesidad que ya no
 * admite propuestas (cerrada, vencida, cancelada, suspendida o aún en
 * borrador).
 */
class NeedClosedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Esta solicitud ya no admite propuestas.');
    }
}
