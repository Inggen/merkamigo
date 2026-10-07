<?php

namespace App\Domain\Loyalty\Support;

use Illuminate\Support\Str;

/**
 * Generación y verificación de tokens opacos (TODO_Merkapuntos.md, F1.8):
 * "ambos tokens opacos con tipo validado. No exponer teléfono, email, ID
 * secuencial o permisos." Solo el hash se persiste; el token crudo se
 * entrega una única vez a quien lo solicita.
 */
class LoyaltyTokens
{
    /**
     * @return array{token: string, hash: string}
     */
    public static function generate(string $prefix): array
    {
        // Prefijo visible (no secreto) para que un lector humano del log
        // distinga de un vistazo identidad vs. canje sin decodificar nada.
        $token = $prefix.'_'.Str::random(40);

        return [
            'token' => $token,
            'hash' => self::hash($token),
        ];
    }

    public static function hash(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }
}
