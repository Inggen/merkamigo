<?php

namespace App\Support\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rechaza enlaces (http/https/www) en campos de texto público (1.4 del
 * TODO: "validación de contenido prohibido"). Se enfoca en spam de
 * enlaces —el riesgo real y verificable— en vez de un listado de
 * palabras prohibidas inventado.
 */
class NoLinks implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (preg_match('/(https?:\/\/|www\.)\S+/i', $value) === 1) {
            $fail('El campo :attribute no puede contener enlaces.');
        }
    }
}
