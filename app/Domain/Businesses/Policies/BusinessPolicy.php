<?php

namespace App\Domain\Businesses\Policies;

use App\Domain\Businesses\Models\Business;
use App\Models\User;

/**
 * Autorización por negocio (0.5 del TODO: ningún negocio puede consultar o
 * modificar información privada de otro; un colaborador no administra
 * negocios ajenos ni escala su propio rol).
 *
 * Asume que el "team" activo de spatie/laravel-permission ya fue fijado al
 * negocio de la ruta por el middleware `business.team`.
 */
class BusinessPolicy
{
    public function view(User $user, Business $business): bool
    {
        return $user->hasAnyRole(['owner', 'admin', 'collaborator']);
    }

    public function update(User $user, Business $business): bool
    {
        return $user->hasAnyRole(['owner', 'admin']);
    }

    public function manageMembers(User $user, Business $business): bool
    {
        return $user->hasAnyRole(['owner', 'admin']);
    }

    public function delete(User $user, Business $business): bool
    {
        return $user->hasRole('owner');
    }
}
