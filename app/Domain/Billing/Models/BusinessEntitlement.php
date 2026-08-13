<?php

namespace App\Domain\Billing\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Capacidad desbloqueada para un negocio (ej. el chatbot con IA de la
 * vitrina), otorgada por la compra de un `BillingProduct` (kind
 * `entitlement`). Una fila por clave por negocio — comprar de nuevo
 * extiende `expires_at` en vez de duplicar la fila (ver
 * `ApplyBillingProductPurchase::applyEntitlement()`).
 */
class BusinessEntitlement extends Model
{
    public const AI_CHATBOT = 'ai_chatbot';

    protected $fillable = [
        'business_id',
        'key',
        'source_billing_product_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<BillingProduct, $this>
     */
    public function sourceBillingProduct(): BelongsTo
    {
        return $this->belongsTo(BillingProduct::class, 'source_billing_product_id');
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
