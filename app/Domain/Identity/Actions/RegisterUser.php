<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Concerns\PasswordValidationRules;
use App\Domain\Identity\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Acción de dominio del módulo Identity. Se invoca tanto desde Fortify
 * (registro web) como desde `POST /api/v1/auth/register`, para no duplicar
 * las reglas de registro entre la web y la API (0.4 del TODO).
 */
class RegisterUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $validated = Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        // Si el invitado ya eligió "comprar/encontrar" o "vender/mostrar mi
        // negocio" desde la landing (0.2 del TODO), la nueva cuenta arranca
        // en esa experiencia en vez de quedar sin definir.
        $intendedExperience = Cookie::get('experience');

        return User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'experience' => in_array($intendedExperience, ['cliente', 'emprendedor'], true) ? $intendedExperience : null,
        ]);
    }
}
