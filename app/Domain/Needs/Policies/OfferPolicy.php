<?php

namespace App\Domain\Needs\Policies;

use App\Domain\Needs\Models\Offer;
use App\Models\User;

/**
 * Una propuesta pertenece al negocio que la envió (5.1 del TODO: `DELETE
 * offers/{offer}` de la API necesitaba una regla explícita que no existía
 * — el equivalente web ya verificaba pertenencia inline, sin policy).
 */
class OfferPolicy
{
    public function withdraw(User $user, Offer $offer): bool
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId($offer->business_id);
        $user->unsetRelation('roles');
        $isMember = $user->hasAnyRole(['owner', 'admin', 'collaborator']);

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');

        return $isMember;
    }
}
