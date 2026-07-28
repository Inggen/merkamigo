<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;

/**
 * Revierte la suspensión de un producto (1.9 del TODO).
 */
class RestoreProduct
{
    public function handle(Product $product, User $moderator): void
    {
        $product->update([
            'status' => 'publicado',
            'suspension_reason' => null,
            'suspended_at' => null,
        ]);

        app(RecordAuditLog::class)->handle($moderator, 'product.restored', $product);
    }
}
