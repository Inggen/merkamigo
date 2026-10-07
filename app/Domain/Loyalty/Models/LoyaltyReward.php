<?php

namespace App\Domain\Loyalty\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Premio publicado por un negocio (TODO_Merkapuntos.md, F3.5). El
 * presupuesto y el stock se comprometen aquí mismo con columnas agregadas
 * (`*_reserved_cents`/`stock_reserved`), protegidas con `lockForUpdate()`
 * en `ReserveLoyaltyRedemption` para que dos canjes simultáneos nunca
 * gasten la misma unidad o el mismo presupuesto dos veces.
 *
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 */
class LoyaltyReward extends Model
{
    public const PRODUCTO = 'producto';

    public const DESCUENTO = 'descuento';

    public const REGALO = 'regalo';

    public const BORRADOR = 'borrador';

    public const PUBLICADO = 'publicado';

    public const PAUSADO = 'pausado';

    public const AGOTADO = 'agotado';

    public const ARCHIVADO = 'archivado';

    protected $fillable = [
        'business_id',
        'product_id',
        'image_path',
        'type',
        'title',
        'slug',
        'description',
        'points_cost',
        'full_cost_cents',
        'stock_total',
        'stock_reserved',
        'stock_delivered',
        'max_budget_cents',
        'budget_reserved_cents',
        'budget_spent_cents',
        'valid_from',
        'valid_until',
        'status',
        'version',
        'terms',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'full_cost_cents' => 'integer',
            'stock_total' => 'integer',
            'stock_reserved' => 'integer',
            'stock_delivered' => 'integer',
            'max_budget_cents' => 'integer',
            'budget_reserved_cents' => 'integer',
            'budget_spent_cents' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $reward): void {
            if (! $reward->slug || $reward->isDirty('title')) {
                $reward->slug = $reward->uniqueSlug($reward->title);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($field) {
            return parent::resolveRouteBindingQuery($query, $value, $field);
        }

        return $query->where(function ($query) use ($value): void {
            $query->where('slug', $value);

            if (ctype_digit((string) $value)) {
                $query->orWhere($this->getKeyName(), $value);
            }
        });
    }

    public function stockAvailable(): ?int
    {
        if ($this->stock_total === null) {
            return null;
        }

        return max(0, $this->stock_total - $this->stock_reserved - $this->stock_delivered);
    }

    public function budgetAvailableCents(): ?int
    {
        if ($this->max_budget_cents === null) {
            return null;
        }

        return max(0, $this->max_budget_cents - $this->budget_reserved_cents - $this->budget_spent_cents);
    }

    public function isWithinValidity(?Carbon $at = null): bool
    {
        $at ??= now();

        if ($this->valid_from && $at->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $at->gt($this->valid_until->endOfDay())) {
            return false;
        }

        return true;
    }

    public function isRedeemable(): bool
    {
        if ($this->status !== self::PUBLICADO || ! $this->isWithinValidity()) {
            return false;
        }

        if ($this->stockAvailable() === 0) {
            return false;
        }

        if ($this->budgetAvailableCents() === 0) {
            return false;
        }

        return true;
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

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'premio';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
