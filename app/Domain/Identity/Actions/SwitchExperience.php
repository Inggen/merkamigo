<?php

namespace App\Domain\Identity\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use InvalidArgumentException;

/**
 * Cambia la experiencia activa (Cliente/Emprendedor) sin cerrar sesión ni
 * duplicar cuenta, y la recuerda entre visitas (0.2/0.2.1 del TODO).
 *
 * Un usuario autenticado la guarda en su perfil; un invitado la guarda en
 * una cookie de un año hasta que se registre.
 */
class SwitchExperience
{
    public const OPTIONS = ['cliente', 'emprendedor'];

    public function handle(?User $user, string $experience): void
    {
        if (! in_array($experience, self::OPTIONS, true)) {
            throw new InvalidArgumentException("Experiencia inválida: {$experience}");
        }

        if ($user) {
            $user->forceFill(['experience' => $experience])->save();

            return;
        }

        Cookie::queue('experience', $experience, 60 * 24 * 365);
    }
}
