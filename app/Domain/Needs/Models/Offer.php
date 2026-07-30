<?php

namespace App\Domain\Needs\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Propuesta de un negocio a una `Need` (Fase 2 del TODO).
 *
 * @property Carbon|null $viewed_at
 * @property Carbon|null $withdrawn_at
 */
class Offer extends Model
{
    public const ENVIADA = 'enviada';

    public const VISTA = 'vista';

    public const PRESELECCIONADA = 'preseleccionada';

    public const ACEPTADA = 'aceptada';

    public const RECHAZADA = 'rechazada';

    public const RETIRADA = 'retirada';

    protected $fillable = [
        'need_id',
        'business_id',
        'product_id',
        'message',
        'price',
        'availability',
        'status',
        'viewed_at',
        'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'viewed_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Need, $this>
     */
    public function need(): BelongsTo
    {
        return $this->belongsTo(Need::class);
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, [self::RECHAZADA, self::RETIRADA], true);
    }

    public function isWithdrawn(): bool
    {
        return $this->status === self::RETIRADA;
    }
}
