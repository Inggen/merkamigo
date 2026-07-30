<?php

namespace App\Domain\Needs\Policies;

use App\Domain\Needs\Models\Need;
use App\Models\User;

/**
 * Una necesidad pertenece a un comprador (User), no a un negocio — no usa
 * el sistema de teams de spatie/laravel-permission como `BusinessPolicy`,
 * solo comparación directa de dueño (2.1 del TODO: el comprador controla
 * su propia solicitud).
 */
class NeedPolicy
{
    public function view(User $user, Need $need): bool
    {
        return $need->user_id === $user->id;
    }

    public function update(User $user, Need $need): bool
    {
        return $need->user_id === $user->id;
    }

    public function delete(User $user, Need $need): bool
    {
        return $need->user_id === $user->id;
    }
}
