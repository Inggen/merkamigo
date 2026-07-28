<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Suspende un negocio con motivo obligatorio (1.9 del TODO: "toda
 * suspensión requiere motivo y queda auditada"). El motivo queda visible
 * para el propio emprendedor en su panel — no hay canal de correo/SMS real
 * todavía, así que la "notificación al afectado" es ese aviso en su panel.
 */
class SuspendBusiness
{
    public function handle(Business $business, User $moderator, string $reason): void
    {
        $business->update([
            'status' => 'suspendido',
            'suspension_reason' => $reason,
            'suspended_at' => now(),
        ]);

        app(RecordAuditLog::class)->handle($moderator, 'business.suspended', $business, [
            'reason' => $reason,
        ]);
    }
}
