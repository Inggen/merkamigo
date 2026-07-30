<?php

namespace App\Domain\Trust\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderConfirmation extends Model
{
    public const PENDIENTE = 'pendiente_confirmacion';

    public const CONFIRMADO = 'confirmado_por_ambos';

    public const COMPLETADO = 'completado';

    public const CANCELADO = 'cancelado';

    public const EN_DISPUTA = 'en_disputa';

    protected $fillable = [
        'business_id',
        'customer_user_id',
        'business_user_id',
        'created_by',
        'source_type',
        'source_id',
        'status',
        'customer_confirmed_at',
        'business_confirmed_at',
        'completed_at',
        'summary',
        'dispute_note',
        'is_reputation_eligible',
    ];

    protected function casts(): array
    {
        return [
            'customer_confirmed_at' => 'datetime',
            'business_confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_reputation_eligible' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function businessUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function canBeCompleted(): bool
    {
        return $this->status === self::CONFIRMADO;
    }
}
