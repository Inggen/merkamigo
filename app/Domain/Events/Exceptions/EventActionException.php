<?php

namespace App\Domain\Events\Exceptions;

use RuntimeException;

/**
 * Error de negocio del módulo de Eventos (franja no disponible, falta
 * Wompi verificado, inventario de equipo insuficiente...) — mensaje
 * pensado para mostrarse directamente al dueño del negocio o al
 * prospecto, no un error técnico.
 */
class EventActionException extends RuntimeException {}
