<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Revierte una suspensión (1.9 del TODO: "es posible revertir una
 * moderación autorizada"). Solo se suspenden negocios que ya estaban
 * publicados, así que restaurar siempre vuelve a "publicado".
 */
class RestoreBusiness
{
    public function handle(Business $business, User $moderator): void
    {
        $business->update([
            'status' => 'publicado',
            'suspension_reason' => null,
            'suspended_at' => null,
        ]);

        app(RecordAuditLog::class)->handle($moderator, 'business.restored', $business);
    }
}
