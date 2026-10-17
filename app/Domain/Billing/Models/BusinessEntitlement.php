<?php

namespace App\Domain\Billing\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Capacidad desbloqueada para un negocio (ej. el chatbot con IA de la
 * vitrina), otorgada por la compra de un `BillingProduct` (kind
 * `entitlement`). Una fila por clave por negocio — comprar de nuevo
 * extiende `expires_at` en vez de duplicar la fila (ver
 * `ApplyBillingProductPurchase::applyEntitlement()`).
 *
 * `status`/`grace_ends_at` (PR4 de TODO_VENTAS_RENTABILIDAD.md, pedido
 * del usuario: "deja el cobro mensual igual de robusto que la
 * renovación de planes") — mismo periodo de gracia que `Subscription`:
 * si el cobro automático falla o no hay tarjeta guardada, el negocio no
 * pierde el acceso de inmediato. Solo tiene sentido para un entitlement
 * recurrente (`expires_at` no nulo); uno de por vida se queda siempre en
 * `ACTIVA` porque nunca entra a `ProcessEntitlementRenewals`.
 *
 * @property Carbon|null $expires_at
 * @property Carbon|null $grace_ends_at
 * @property-read BillingProduct|null $sourceBillingProduct
 */
class BusinessEntitlement extends Model
{
    public const AI_CHATBOT = 'ai_chatbot';

    public const ACTIVA = 'activa';

    public const EN_GRACIA = 'en_gracia';

    protected $fillable = [
        'business_id',
        'key',
        'status',
        'source_billing_product_id',
        'expires_at',
        'grace_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'grace_ends_at' => 'datetime',
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

    /**
     * De por vida (`expires_at` nulo): siempre activo. Recurrente
     * vigente: activo mientras no haya vencido. Recurrente vencido pero
     * todavía en gracia: sigue activo — ese es justamente el propósito
     * de la gracia, no perder el acceso de inmediato. Vencido sin
     * gracia (o gracia ya agotada): inactivo.
     */
    public function isActive(): bool
    {
        if ($this->expires_at === null) {
            return true;
        }

        if ($this->status === self::EN_GRACIA) {
            return $this->grace_ends_at === null || $this->grace_ends_at->isFuture();
        }

        return $this->expires_at->isFuture();
    }
}
