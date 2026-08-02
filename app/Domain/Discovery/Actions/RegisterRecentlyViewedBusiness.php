<?php

namespace App\Domain\Discovery\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\RecentlyViewedBusiness;
use App\Models\User;

/**
 * Historial básico de negocios vistos (1.1.1 del TODO), solo con
 * consentimiento explícito del Cliente (`$user->remember_recently_viewed`,
 * activable desde su perfil). Se conservan como máximo los últimos
 * {@see self::MAX_PER_USER} negocios distintos por usuario.
 */
class RegisterRecentlyViewedBusiness
{
    private const MAX_PER_USER = 20;

    public function handle(User $user, Business $business): void
    {
        if (! $user->remember_recently_viewed) {
            return;
        }

        RecentlyViewedBusiness::updateOrCreate(
            ['user_id' => $user->id, 'business_id' => $business->id],
            ['viewed_at' => now()],
        );

        $keepIds = RecentlyViewedBusiness::where('user_id', $user->id)
            ->orderByDesc('viewed_at')
            ->take(self::MAX_PER_USER)
            ->pluck('id');

        RecentlyViewedBusiness::where('user_id', $user->id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }
}
