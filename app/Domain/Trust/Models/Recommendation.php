<?php

namespace App\Domain\Trust\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $published_at
 * @property Carbon|null $moderated_at
 */
class Recommendation extends Model
{
    public const PENDIENTE = 'pendiente';

    public const PUBLICADA = 'publicada';

    public const OCULTA = 'oculta';

    /**
     * Etiquetas sugeridas al recomendar (3.3 del TODO: "texto corto y
     * etiquetas útiles", sin puntuaciones numéricas complejas).
     *
     * @var array<int, string>
     */
    public const SUGGESTED_TAGS = [
        'Cumplió a tiempo',
        'Buena atención',
        'Precio justo',
        'Lo recomiendo',
    ];

    protected $fillable = [
        'business_id',
        'order_confirmation_id',
        'author_user_id',
        'status',
        'body',
        'tags',
        'business_response',
        'published_at',
        'moderated_by',
        'moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'published_at' => 'datetime',
            'moderated_at' => 'datetime',
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
     * @return BelongsTo<OrderConfirmation, $this>
     */
    public function orderConfirmation(): BelongsTo
    {
        return $this->belongsTo(OrderConfirmation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}
