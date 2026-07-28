<?php

namespace App\Domain\Businesses\Exceptions;

use RuntimeException;

/**
 * Error de negocio al invitar un colaborador (usuario inexistente o ya
 * miembro) — se muestra directamente al emprendedor, no es un error técnico.
 */
class CollaboratorInviteException extends RuntimeException {}
