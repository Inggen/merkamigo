<?php

namespace App\Domain\Businesses\Models;

use App\Domain\Discovery\Concerns\Favoritable;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Storefronts\Models\Storefront;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property array<string, mixed>|null $hours
 * @property array<string, string>|null $social_links
 * @property array<string, mixed>|null $attributes
 * @property Carbon|null $suspended_at
 * @property Carbon|null $featured_until
 */
class Business extends Model
{
    use Favoritable, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'municipality_id',
        'category_id',
        'name',
        'slug',
        'zone',
        'address',
        'whatsapp_number',
        'logo_path',
        'hours',
        'social_links',
        'payment_info',
        'attributes',
        'status',
        'suspension_reason',
        'suspended_at',
        'featured_until',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'array',
            'social_links' => 'array',
            'attributes' => 'array',
            'suspended_at' => 'datetime',
            'featured_until' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasOne<Storefront, $this>
     */
    public function storefront(): HasOne
    {
        return $this->hasOne(Storefront::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('position');
    }

    public function isPublished(): bool
    {
        return $this->status === 'publicado';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspendido';
    }

    public function isFeatured(): bool
    {
        return $this->featured_until !== null && $this->featured_until->isFuture();
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /**
     * Texto libre de horario guardado en `hours` (0.6/1.3 del TODO: sin un
     * horario estructurado por día todavía, solo una nota editable).
     */
    public function hoursNote(): ?string
    {
        return $this->hours['note'] ?? null;
    }

    /**
     * Usuarios con una membresía en este negocio. El rol de cada uno vive en
     * spatie/laravel-permission (team = este negocio), no en la tabla pivote.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_memberships')
            ->withPivot(['status'])
            ->withTimestamps();
    }
}
