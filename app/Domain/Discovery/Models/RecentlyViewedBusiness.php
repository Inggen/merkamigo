<?php

namespace App\Domain\Discovery\Models;

use App\Domain\Businesses\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial básico de negocios vistos (1.1.1 del TODO), guardado solo
 * cuando el Cliente dio su consentimiento explícito (`users.remember_recently_viewed`).
 */
class RecentlyViewedBusiness extends Model
{
    protected $fillable = ['user_id', 'business_id', 'viewed_at'];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
