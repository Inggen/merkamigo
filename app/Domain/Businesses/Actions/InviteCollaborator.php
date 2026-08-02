<?php

namespace App\Domain\Businesses\Actions;

use App\Domain\Billing\Actions\CheckUsageLimit;
use App\Domain\Businesses\Exceptions\CollaboratorInviteException;
use App\Domain\Businesses\Models\Business;
use App\Domain\Businesses\Models\BusinessMembership;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Gestión básica de colaboradores (1.6 del TODO). Simplificación de MVP:
 * agrega directamente a un usuario ya registrado (sin sistema de invitación
 * por correo todavía) con rol admin o collaborator; el owner no se
 * gestiona por aquí.
 */
class InviteCollaborator
{
    public function handle(Business $business, User $actor, string $email, string $role): BusinessMembership
    {
        if (! in_array($role, ['admin', 'collaborator'], true)) {
            throw new CollaboratorInviteException('Rol inválido.');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            throw new CollaboratorInviteException('No encontramos ninguna cuenta de Merkamigo con ese correo. Pídele que se registre primero.');
        }

        if ($business->members()->where('users.id', $user->id)->exists()) {
            throw new CollaboratorInviteException('Esa persona ya hace parte de este negocio.');
        }

        $usage = app(CheckUsageLimit::class)->handle($business, 'max_members');

        if (! $usage['allowed']) {
            throw new CollaboratorInviteException("Tu plan permite hasta {$usage['limit']} miembros en el negocio. Mejora tu plan para agregar más.");
        }

        return DB::transaction(function () use ($business, $actor, $user, $role) {
            $membership = BusinessMembership::create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'status' => 'activo',
            ]);

            $previousTeamId = getPermissionsTeamId();
            setPermissionsTeamId($business->id);
            $user->unsetRelation('roles');
            $user->assignRole(Role::findOrCreate($role, 'web'));
            setPermissionsTeamId($previousTeamId);

            app(RecordAuditLog::class)->handle($actor, 'business.collaborator_added', $business, [
                'user_id' => $user->id,
                'role' => $role,
            ]);

            return $membership;
        });
    }
}
