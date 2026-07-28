<?php

namespace App\Domain\Storefronts\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Concerns\Favoritable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use Favoritable, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'type',
        'description',
        'price',
        'price_type',
        'unit',
        'promo_price',
        'promo_label',
        'is_available',
        'status',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'promo_price' => 'decimal:2',
            'is_available' => 'boolean',
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
     * @return HasMany<ProductMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('position');
    }

    public function isPublished(): bool
    {
        return $this->status === 'publicado';
    }
}
