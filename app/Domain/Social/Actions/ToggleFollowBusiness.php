<?php

namespace App\Domain\Social\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Models\Follow;
use App\Domain\Social\Notifications\NewFollower;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/**
 * Seguir/dejar de seguir un negocio (2.3 del TODO social, Sprint 2).
 */
class ToggleFollowBusiness
{
    /**
     * @return bool true si quedó siguiendo, false si dejó de seguir.
     */
    public function handle(User $user, Business $business): bool
    {
        $existing = Follow::query()
            ->where('user_id', $user->id)
            ->where('business_id', $business->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        Follow::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
        ]);

        // Fase 11 del TODO social ("nuevo seguidor"): a todos los
        // miembros activos del negocio, no solo al dueño.
        $members = $business->members()->wherePivot('status', 'activo')->get();

        if ($members->isNotEmpty()) {
            Notification::send($members, new NewFollower($user, $business->id, $business->name));
        }

        return true;
    }
}
