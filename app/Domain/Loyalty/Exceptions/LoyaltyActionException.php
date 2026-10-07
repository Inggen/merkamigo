<?php

namespace App\Domain\Loyalty\Exceptions;

use RuntimeException;

/**
 * Error de negocio del programa de recompensas (saldo insuficiente, premio
 * agotado, token inválido/expirado, política faltante...) — mensaje
 * pensado para mostrarse directamente al cliente o al empleado, no un
 * error técnico.
 */
class LoyaltyActionException extends RuntimeException {}
