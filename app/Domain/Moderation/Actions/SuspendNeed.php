<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Needs\Models\Need;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Suspende una necesidad por moderación (2.1 del TODO: "moderación
 * automática básica y revisión manual"). A diferencia de un producto, no
 * reutiliza `status` para esto — `suspended_at` ya es lo único que
 * `Need::isOpenForOffers()` necesita para dejar de admitir propuestas sin
 * perder el estado real (publicada, recibiendo_ofertas...) al restaurarla.
 */
class SuspendNeed
{
    public function handle(Need $need, User $moderator, string $reason): void
    {
        $need->update([
            'suspension_reason' => $reason,
            'suspended_at' => now(),
        ]);

        app(RecordAuditLog::class)->handle($moderator, 'need.suspended', $need, [
            'reason' => $reason,
        ]);
    }
}
