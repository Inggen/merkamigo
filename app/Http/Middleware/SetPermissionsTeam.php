<?php

namespace App\Http\Middleware;

use App\Domain\Businesses\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija el "team" (negocio) activo para spatie/laravel-permission antes de
 * que se evalúen roles/policies, a partir del parámetro de ruta `business`
 * (0.5 del TODO: roles y permisos por negocio).
 */
class SetPermissionsTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        $business = $request->route('business');

        $businessId = match (true) {
            $business instanceof Business => $business->id,
            is_string($business) => $business,
            default => null,
        };

        setPermissionsTeamId($businessId);

        // Si el usuario autenticado ya tenía la relación `roles` cargada en
        // memoria (p. ej. porque el mismo objeto se reutiliza dentro de un
        // job o comando que recorre varios negocios), hay que descartarla:
        // de lo contrario spatie/permission seguiría evaluando roles del
        // team anterior en vez de volver a consultar con el nuevo team.
        $request->user()?->unsetRelation('roles');

        return $next($request);
    }
}
