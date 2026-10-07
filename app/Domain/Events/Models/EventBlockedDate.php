<?php

namespace App\Domain\Events\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Fecha en la que un negocio no acepta reservas de eventos (cierre,
 * mantenimiento, evento privado ya acordado fuera del sistema, etc.).
 *
 * @property Carbon $date
 */
class EventBlockedDate extends Model
{
    protected $fillable = ['business_id', 'date', 'reason'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
