<?php

namespace App\Domain\Needs\Models;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * "Pídelo en Merkamigo" (Fase 2 del TODO): un comprador publica lo que
 * necesita y recibe propuestas (`Offer`) de negocios cercanos.
 *
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $suspended_at
 */
class Need extends Model
{
    use SoftDeletes;

    public const BORRADOR = 'borrador';

    public const PUBLICADA = 'publicada';

    public const RECIBIENDO_OFERTAS = 'recibiendo_ofertas';

    public const SELECCIONADA = 'seleccionada';

    public const CERRADA = 'cerrada';

    public const VENCIDA = 'vencida';

    public const CANCELADA = 'cancelada';

    public const OUTCOME_CONTACTE = 'contacte';

    public const OUTCOME_ENCONTRE = 'encontre';

    public const OUTCOME_NO_ENCONTRE = 'no_encontre';

    /** Días por defecto hasta que una necesidad publicada expira (2.1 del TODO). */
    public const DEFAULT_LIFETIME_DAYS = 14;

    protected $fillable = [
        'user_id',
        'municipality_id',
        'category_id',
        'zone',
        'title',
        'description',
        'budget',
        'status',
        'outcome',
        'selected_offer_id',
        'published_at',
        'expires_at',
        'closed_at',
        'suspension_reason',
        'suspended_at',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'closed_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * @return HasMany<NeedMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(NeedMedia::class)->orderBy('position');
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class)->latest();
    }

    public function selectedOffer(): ?Offer
    {
        if (! $this->selected_offer_id) {
            return null;
        }

        return $this->relationLoaded('offers')
            ? $this->offers->firstWhere('id', $this->selected_offer_id)
            : Offer::find($this->selected_offer_id);
    }

    public function isDraft(): bool
    {
        return $this->status === self::BORRADOR;
    }

    public function isPublished(): bool
    {
        return in_array($this->status, [self::PUBLICADA, self::RECIBIENDO_OFERTAS, self::SELECCIONADA], true);
    }

    public function isOpenForOffers(): bool
    {
        return $this->isPublished() && ! $this->isExpired() && $this->suspended_at === null;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::CERRADA, self::VENCIDA, self::CANCELADA], true);
    }
}
