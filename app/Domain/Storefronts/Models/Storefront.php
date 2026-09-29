<?php

namespace App\Domain\Storefronts\Models;

use App\Domain\Businesses\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Storefront extends Model
{
    protected $fillable = [
        'business_id',
        'headline',
        'description',
        'cover_path',
        'cover_alt_text',
        'stand_color',
        'show_posts',
        'show_reels',
        'google_maps_embed_url',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'show_posts' => 'boolean',
            'show_reels' => 'boolean',
        ];
    }

    public static function normalizeGoogleMapsEmbedUrl(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $value, $matches)) {
            $value = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        }

        $parts = parse_url($value);
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';

        if (($parts['scheme'] ?? null) !== 'https'
            || ! in_array($host, ['google.com', 'www.google.com', 'maps.google.com'], true)
            || ! str_starts_with($path, '/maps/embed')) {
            return null;
        }

        return $value;
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }
}
