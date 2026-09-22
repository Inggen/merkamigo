<?php

namespace App\Domain\Social\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentPromotion extends Model
{
    public const BORRADOR = 'borrador';

    public const ACTIVA = 'activa';

    public const FINALIZADA = 'finalizada';

    protected $fillable = [
        'business_id',
        'created_by_user_id',
        'promotable_type',
        'promotable_id',
        'municipality_id',
        'category_id',
        'radius_km',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function promotable(): MorphTo
    {
        return $this->morphTo();
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVA
            && $this->starts_at?->lte(now())
            && $this->ends_at?->isFuture();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', self::ACTIVA)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }
}
