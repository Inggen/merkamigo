<?php

namespace App\Domain\Businesses\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Cambia el rol de un miembro dentro de un negocio.
 *
 * Regla de 0.5 del TODO: "un colaborador no puede autoescalar su propio
 * rol" — aquí se aplica de forma más estricta: nadie puede cambiar su
 * propio rol (evita también que un admin se autopromueva a owner), y solo
 * el owner puede otorgar el rol de owner.
 */
class UpdateMemberRole
{
    public function handle(User $actor, Business $business, User $member, string $newRole): void
    {
        if ($actor->is($member)) {
            throw new AuthorizationException('No puedes cambiar tu propio rol.');
        }

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($business->id);

        try {
            if ($newRole === 'owner' && ! $actor->hasRole('owner')) {
                throw new AuthorizationException('Solo el propietario puede transferir la propiedad del negocio.');
            }

            DB::transaction(function () use ($member, $newRole, $business, $actor) {
                $member->syncRoles([Role::findOrCreate($newRole, 'web')]);

                app(RecordAuditLog::class)->handle($actor, 'business.member_role_updated', $business, [
                    'member_id' => $member->id,
                    'role' => $newRole,
                ]);
            });
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }
}
