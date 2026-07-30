<?php

namespace App\Domain\Trust\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends Model
{
    public const PENDIENTE = 'pendiente';

    public const PUBLICADA = 'publicada';

    public const OCULTA = 'oculta';

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

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function orderConfirmation(): BelongsTo
    {
        return $this->belongsTo(OrderConfirmation::class);
    }

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}
