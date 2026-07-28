<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;

/**
 * Suspende un producto por moderación (1.9 del TODO). Reutiliza el estado
 * "archivado" (deja de mostrarse públicamente, igual que si el emprendedor
 * lo archivara) pero guarda `suspended_at`/`suspension_reason` para
 * diferenciarlo de un archivado voluntario y poder revertirlo.
 */
class SuspendProduct
{
    public function handle(Product $product, User $moderator, string $reason): void
    {
        $product->update([
            'status' => 'archivado',
            'suspension_reason' => $reason,
            'suspended_at' => now(),
        ]);

        app(RecordAuditLog::class)->handle($moderator, 'product.suspended', $product, [
            'reason' => $reason,
        ]);
    }
}
