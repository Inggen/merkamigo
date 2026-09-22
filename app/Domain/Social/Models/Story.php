<?php

namespace App\Domain\Social\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Concerns\Promotable;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Estado Merkamigo (Fase 3 de TODO_social.md, Sprint 3): contenido
 * efímero de 24h por defecto.
 */
class Story extends Model
{
    use Promotable, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'product_id',
        'type',
        'image_path',
        'caption',
        'expires_at',
        'views_count',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<StoryView, $this>
     */
    public function views(): HasMany
    {
        return $this->hasMany(StoryView::class);
    }

    public function imageUrl(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isExpired();
    }

    public function isViewedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->views()->where('user_id', $user->id)->exists();
    }
}
