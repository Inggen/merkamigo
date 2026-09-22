<?php

namespace App\Domain\Social\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Concerns\Favoritable;
use App\Domain\Social\Concerns\Promotable;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Publicación de un negocio (Fase 2.2 de TODO_social.md, Sprint 2).
 * `Favoritable` se reutiliza tal cual para "guardados" (2.2/12 del TODO)
 * — no hace falta una tabla `saved_items` nueva, `favorites` ya es
 * polimórfica.
 */
class Post extends Model
{
    use Favoritable, Promotable, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'type',
        'body',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
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
     * @return HasMany<PostMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('position');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'post_product')->withTimestamps();
    }

    /**
     * @return HasMany<PostReaction, $this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }

    /**
     * @return HasMany<PostComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    /**
     * Comentarios visibles públicamente (excluye ocultos por moderación).
     *
     * @return HasMany<PostComment, $this>
     */
    public function visibleComments(): HasMany
    {
        return $this->comments()->where('status', 'publicado')->latest();
    }

    public function isPublished(): bool
    {
        return $this->status === 'publicado';
    }

    public function isReactedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->reactions()->where('user_id', $user->id)->exists();
    }
}
