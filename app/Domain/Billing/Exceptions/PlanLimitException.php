<?php

namespace App\Domain\Billing\Exceptions;

use RuntimeException;

/**
 * El negocio alcanzó un límite de su plan actual (4.1 del TODO) — se
 * muestra directamente al emprendedor, no es un error técnico.
 */
class PlanLimitException extends RuntimeException {}
