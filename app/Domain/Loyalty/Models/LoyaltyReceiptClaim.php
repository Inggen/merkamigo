<?php

namespace App\Domain\Loyalty\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Solicitud excepcional con recibo (TODO_Merkapuntos.md, F2.7): un cliente
 * que olvidó mostrar su QR al pagar sube un recibo privado para que el
 * negocio lo revise manualmente. No hay acreditación automática por
 * imagen — un empleado la vincula a una compra existente o crea una
 * acreditación única, o la rechaza con motivo.
 */
class LoyaltyReceiptClaim extends Model
{
    public const PENDIENTE = 'pendiente';

    public const VINCULADA = 'vinculada';

    public const APROBADA = 'aprobada';

    public const RECHAZADA = 'rechazada';

    protected $fillable = [
        'business_id',
        'customer_user_id',
        'receipt_path',
        'description',
        'status',
        'linked_purchase_id',
        'reviewed_by_user_id',
        'reviewed_at',
        'decision_reason',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    /**
     * @return BelongsTo<LoyaltyPurchase, $this>
     */
    public function linkedPurchase(): BelongsTo
    {
        return $this->belongsTo(LoyaltyPurchase::class, 'linked_purchase_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * Enlace firmado temporal al recibo (mismo patrón que
     * `BusinessVerification::documentUrl()`) — nunca una URL directa y
     * permanente al archivo privado.
     */
    public function receiptUrl(): ?string
    {
        return $this->receipt_path
            ? Storage::disk('private')->temporaryUrl($this->receipt_path, now()->addMinutes(10))
            : null;
    }
}
