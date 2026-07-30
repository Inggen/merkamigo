<?php

namespace App\Domain\Discovery\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Municipality extends Model
{
    protected $fillable = ['name', 'slug', 'department', 'cover_path', 'latitude', 'longitude', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * @return HasMany<Business, $this>
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function canAutoDetect(): bool
    {
        return filled($this->latitude) && filled($this->longitude);
    }
}
