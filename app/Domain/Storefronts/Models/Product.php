<?php

namespace App\Domain\Storefronts\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Concerns\Favoritable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $promo_starts_at
 * @property Carbon|null $promo_ends_at
 */
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
        'promo_starts_at',
        'promo_ends_at',
        'is_available',
        'status',
        'position',
        'suspension_reason',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'promo_price' => 'decimal:2',
            'promo_starts_at' => 'datetime',
            'promo_ends_at' => 'datetime',
            'is_available' => 'boolean',
            'suspended_at' => 'datetime',
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

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function isPublished(): bool
    {
        return $this->status === 'publicado';
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Agotado/disponible (1.4 del TODO): reutiliza `is_available`, ya
     * editable desde el formulario, en vez de un segundo mecanismo vía
     * `status`.
     */
    public function isSoldOut(): bool
    {
        return ! $this->is_available;
    }

    public function hasActivePromo(): bool
    {
        if (! $this->promo_price) {
            return false;
        }

        if ($this->promo_starts_at && $this->promo_starts_at->isFuture()) {
            return false;
        }

        if ($this->promo_ends_at && $this->promo_ends_at->isPast()) {
            return false;
        }

        return true;
    }
}
