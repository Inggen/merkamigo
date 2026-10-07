<?php

namespace App\Domain\Loyalty\Actions\Concerns;

use App\Domain\Businesses\Models\Business;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Models\User;

/**
 * Chequeos de rol de Merkamigo Premia (TODO_Merkapuntos.md, roles y
 * permisos). Cada método fija el "team" (negocio) de spatie/laravel-permission
 * explícitamente antes de preguntar el rol y lo restaura al salir — no basta
 * con confiar en que la ruta ya aplicó el middleware `business.team`, porque
 * estas acciones también se invocan desde comandos, colas o pruebas sin ese
 * contexto ambiental.
 */
trait AuthorizesLoyaltyEmployees
{
    /**
     * "Empleado autorizado": cualquier miembro activo del negocio (`owner`,
     * `admin` o `collaborator`) puede operar el escáner.
     *
     * @throws LoyaltyActionException
     */
    protected function assertAuthorizedEmployee(Business $business, User $actor): void
    {
        if (! $this->hasBusinessRole($business, $actor, ['owner', 'admin', 'collaborator'])) {
            throw new LoyaltyActionException('Tu cuenta no tiene permiso para operar Merkapuntos en este negocio.');
        }
    }

    /**
     * Configuración del programa (política, premios): el dueño o un
     * administrador del negocio.
     *
     * @throws LoyaltyActionException
     */
    protected function assertBusinessAdmin(Business $business, User $actor, string $message): void
    {
        if (! $this->hasBusinessRole($business, $actor, ['owner', 'admin'])) {
            throw new LoyaltyActionException($message);
        }
    }

    /**
     * Decisiones de adhesión/retiro del programa: solo el dueño, nunca un
     * administrador delegado.
     *
     * @throws LoyaltyActionException
     */
    protected function assertBusinessOwner(Business $business, User $actor, string $message): void
    {
        if (! $this->hasBusinessRole($business, $actor, ['owner'])) {
            throw new LoyaltyActionException($message);
        }
    }

    /**
     * @param  array<int, string>  $roles
     */
    private function hasBusinessRole(Business $business, User $actor, array $roles): bool
    {
        $previousTeamId = getPermissionsTeamId();

        try {
            setPermissionsTeamId($business->id);
            $actor->unsetRelation('roles');

            return $actor->hasAnyRole($roles);
        } finally {
            setPermissionsTeamId($previousTeamId);
            $actor->unsetRelation('roles');
        }
    }
}
