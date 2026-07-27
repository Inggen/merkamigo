<?php

namespace App\Domain\Businesses\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Businesses\Models\BusinessMembership;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Agrega un miembro (colaborador/administrador) a un negocio con un rol
 * concreto (0.3 y 1.6 del TODO: gestión de colaboradores).
 */
class AddBusinessMember
{
    public function handle(User $actor, Business $business, User $member, string $role): BusinessMembership
    {
        return DB::transaction(function () use ($actor, $business, $member, $role) {
            $membership = BusinessMembership::create([
                'business_id' => $business->id,
                'user_id' => $member->id,
                'status' => 'activo',
            ]);

            $previousTeamId = getPermissionsTeamId();

            setPermissionsTeamId($business->id);
            $member->assignRole(Role::findOrCreate($role, 'web'));
            setPermissionsTeamId($previousTeamId);

            app(RecordAuditLog::class)->handle($actor, 'business.member_added', $business, [
                'member_id' => $member->id,
                'role' => $role,
            ]);

            return $membership;
        });
    }
}
