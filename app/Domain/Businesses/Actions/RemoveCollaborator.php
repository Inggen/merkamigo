<?php

namespace App\Domain\Businesses\Actions;

use App\Domain\Businesses\Exceptions\CollaboratorInviteException;
use App\Domain\Businesses\Models\Business;
use App\Domain\Businesses\Models\BusinessMembership;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Quita a un colaborador o administrador de un negocio (1.6 del TODO). El
 * owner nunca se puede quitar por aquí — solo eliminando el negocio.
 */
class RemoveCollaborator
{
    public function handle(Business $business, User $actor, BusinessMembership $membership): void
    {
        if ($membership->business_id !== $business->id) {
            throw new CollaboratorInviteException('Esa membresía no pertenece a este negocio.');
        }

        $member = $membership->user;

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($business->id);
        $member->unsetRelation('roles');

        if ($member->hasRole('owner')) {
            setPermissionsTeamId($previousTeamId);

            throw new CollaboratorInviteException('El propietario del negocio no se puede quitar.');
        }

        DB::transaction(function () use ($business, $actor, $membership, $member) {
            $member->syncRoles([]);
            $membership->delete();

            app(RecordAuditLog::class)->handle($actor, 'business.collaborator_removed', $business, [
                'user_id' => $member->id,
            ]);
        });

        setPermissionsTeamId($previousTeamId);
        $member->unsetRelation('roles');
    }
}
