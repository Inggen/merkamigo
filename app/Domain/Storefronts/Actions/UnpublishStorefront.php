<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Storefronts\Models\Storefront;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Vuelve una vitrina publicada a borrador (el emprendedor la retira
 * temporalmente). No borra nada, solo cambia el estado — parte del
 * "estado de publicación" editable que pide 1.6 del TODO.
 */
class UnpublishStorefront
{
    public function handle(Business $business, User $actor): Storefront
    {
        return DB::transaction(function () use ($business, $actor) {
            $business->update(['status' => 'borrador']);
            $business->storefront->update(['status' => 'borrador']);

            app(RecordAuditLog::class)->handle($actor, 'business.unpublished', $business);

            return $business->storefront->setRelation('business', $business);
        });
    }
}
