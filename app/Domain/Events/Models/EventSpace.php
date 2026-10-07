<?php

namespace App\Domain\Events\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Espacio/sala reservable de una vitrina. El esquema admite varios por
 * negocio desde el inicio (reglas de producto), aunque el MVP solo
 * exponga uno en la UI de configuración.
 */
class EventSpace extends Model
{
    protected $fillable = ['business_id', 'name', 'description', 'image_path', 'capacity', 'is_active'];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
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
     * @return HasMany<EventReservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(EventReservation::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
