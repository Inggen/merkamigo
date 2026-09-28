<?php

namespace App\Domain\Messaging\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessConversation extends Model
{
    protected $fillable = [
        'business_id',
        'customer_user_id',
        'context_key',
        'context_type',
        'context_id',
        'context_label',
        'context_url',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
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
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    /**
     * @return HasMany<BusinessMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(BusinessMessage::class);
    }

    /**
     * @param  Builder<BusinessConversation>  $query
     * @return Builder<BusinessConversation>
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            $query->where('customer_user_id', $user->id)
                ->orWhereHas('business.members', fn (Builder $members) => $members
                    ->where('users.id', $user->id)
                    ->where('business_memberships.status', 'activo'));
        });
    }

    public function canBeViewedBy(User $user): bool
    {
        if ($this->customer_user_id === $user->id) {
            return true;
        }

        return $this->business->members()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'activo')
            ->exists();
    }
}
