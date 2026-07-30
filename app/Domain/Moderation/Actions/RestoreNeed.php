<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Needs\Models\Need;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Revierte la suspensión de una necesidad (2.1 del TODO).
 */
class RestoreNeed
{
    public function handle(Need $need, User $moderator): void
    {
        $need->update([
            'suspension_reason' => null,
            'suspended_at' => null,
        ]);

        app(RecordAuditLog::class)->handle($moderator, 'need.restored', $need);
    }
}
