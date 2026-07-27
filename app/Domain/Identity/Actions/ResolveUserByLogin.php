<?php

namespace App\Domain\Identity\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Resuelve un usuario a partir de un identificador que puede ser correo o
 * teléfono, y valida la contraseña. La usan tanto el login web (Fortify)
 * como `POST /api/v1/auth/login`, para no duplicar la regla (0.4 del TODO).
 */
class ResolveUserByLogin
{
    public function handle(string $login, string $password): ?User
    {
        $user = str_contains($login, '@')
            ? User::where('email', $login)->first()
            : User::where('phone', $login)->first();

        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }

        return null;
    }
}
