<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Models\LoyaltyIdentityToken;
use App\Domain\Loyalty\Support\LoyaltyTokens;
use App\Models\User;

/**
 * Emite, reutiliza o rota el QR de identificación del cliente
 * (TODO_Merkapuntos.md, F1.8). El QR tiene que poder mostrarse de nuevo en
 * cada visita a "Merkapuntos" sin cambiar — por eso, a diferencia del
 * token de canje, también se guarda una copia cifrada (reversible con
 * `APP_KEY`) además del hash: el hash es lo único que se usa para
 * resolver un escaneo, la copia cifrada solo se descifra para
 * redisplayárselo a su propio dueño.
 */
class IssueLoyaltyIdentityToken
{
    /**
     * @return string El token crudo, listo para generar el QR.
     */
    public function handle(User $user, bool $forceRotate = false): string
    {
        // Consulta directa en vez de la relación cacheada en `$user`: si
        // el mismo objeto `$user` ya resolvió esta relación antes en el
        // mismo request (por ejemplo como null, antes de que existiera el
        // token), Eloquent devolvería ese resultado viejo en vez de
        // volver a preguntarle a la base de datos.
        $existing = LoyaltyIdentityToken::where('user_id', $user->id)->first();

        if ($existing && ! $forceRotate) {
            return $existing->token_encrypted;
        }

        ['token' => $token, 'hash' => $hash] = LoyaltyTokens::generate('idn');

        LoyaltyIdentityToken::updateOrCreate(
            ['user_id' => $user->id],
            ['token_hash' => $hash, 'token_encrypted' => $token, 'rotated_at' => now()],
        );

        return $token;
    }

    public function resolve(string $rawToken): ?User
    {
        if (! str_starts_with($rawToken, 'idn_')) {
            return null;
        }

        $identity = LoyaltyIdentityToken::where('token_hash', LoyaltyTokens::hash($rawToken))->first();

        return $identity?->user;
    }
}
