<?php

namespace App\Domain\Events\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Plato/bebida ofrecido para eventos. `product_id` es opcional: enlaza al
 * producto de la vitrina cuando existe, pero el nombre y el precio son
 * propios porque el precio de evento puede diferir del precio normal del
 * catálogo (ej. menú especial por persona).
 */
class EventDish extends Model
{
    protected $fillable = [
        'business_id', 'product_id', 'name', 'description', 'price_cents', 'is_available', 'position',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'is_available' => 'boolean',
            'position' => 'integer',
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
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
