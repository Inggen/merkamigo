<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Billing\Models\Plan;
use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Support\Collection;

class ResolveStorefrontQuota
{
    /**
     * @return array{count:int, limit:?int, remaining:?int, can_create:bool}
     */
    public function handle(User $owner): array
    {
        $count = Business::whereHas('organization', fn ($query) => $query->where('owner_user_id', $owner->id))->count();
        $limit = $this->storefrontLimitFor($owner);

        return [
            'count' => $count,
            'limit' => $limit,
            'remaining' => $limit === null ? null : max($limit - $count, 0),
            'can_create' => $limit === null || $count < $limit,
        ];
    }

    private function storefrontLimitFor(User $owner): ?int
    {
        /** @var Collection<int, Plan> $plans */
        $plans = Business::whereHas('organization', fn ($query) => $query->where('owner_user_id', $owner->id))
            ->get()
            ->map(fn (Business $business) => $business->activePlan())
            ->push(Plan::where('slug', 'gratis')->firstOrFail());

        if ($plans->contains(fn (Plan $plan) => $plan->limit('max_storefronts') === null)) {
            return null;
        }

        return $plans->max(fn (Plan $plan) => $plan->limit('max_storefronts'));
    }
}
