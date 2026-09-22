<?php

namespace App\Domain\Social\Models;

use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Social\Concerns\Promotable;
use App\Domain\Storefronts\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class LiveStream extends Model
{
    use Promotable;

    public const BORRADOR = 'borrador';

    public const EN_VIVO = 'en_vivo';

    public const FINALIZADO = 'finalizado';

    protected $fillable = [
        'business_id',
        'user_id',
        'pinned_product_id',
        'title',
        'slug',
        'description',
        'cover_path',
        'intro_video_path',
        'intro_video_active',
        'stream_path',
        'stream_key',
        'stream_origin',
        'signal_status',
        'provider',
        'stream_url',
        'destinations',
        'replay_url',
        'status',
        'scheduled_at',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'destinations' => 'array',
            'stream_key' => 'encrypted',
            'scheduled_at' => 'datetime',
            'intro_video_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Business, $this> */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function pinnedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'pinned_product_id');
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'live_stream_product')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /** @return HasMany<LiveStreamView, $this> */
    public function views(): HasMany
    {
        return $this->hasMany(LiveStreamView::class);
    }

    /** @return HasMany<LiveStreamMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(LiveStreamMessage::class);
    }

    /** @return HasMany<LiveStreamPoll, $this> */
    public function polls(): HasMany
    {
        return $this->hasMany(LiveStreamPoll::class);
    }

    /** @return HasOne<LiveStreamPoll, $this> */
    public function activePoll(): HasOne
    {
        return $this->hasOne(LiveStreamPoll::class)->whereNull('closed_at')->latestOfMany();
    }

    /** @return HasMany<LiveStreamReaction, $this> */
    public function reactions(): HasMany
    {
        return $this->hasMany(LiveStreamReaction::class);
    }

    /** @return HasMany<LiveProductEvent, $this> */
    public function productEvents(): HasMany
    {
        return $this->hasMany(LiveProductEvent::class)->orderBy('elapsed_seconds');
    }

    /** @return HasMany<LiveDestination, $this> */
    public function streamingDestinations(): HasMany
    {
        return $this->hasMany(LiveDestination::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function hlsUrl(): string
    {
        if (config('services.live_streaming.proxy_hls')) {
            return route('streaming.hls', ['liveStream' => $this, 'asset' => 'index.m3u8']);
        }

        return rtrim(config('services.live_streaming.hls_url'), '/').'/'.$this->stream_path.'/index.m3u8';
    }

    public function rtmpIngestUrl(): string
    {
        return rtrim(config('services.live_streaming.rtmp_url'), '/').'/live';
    }

    public function obsStreamKey(): string
    {
        return basename((string) $this->stream_path).'?user=merkamigo&pass='.urlencode((string) $this->stream_key);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function introVideoUrl(): ?string
    {
        return $this->intro_video_path ? Storage::disk('public')->url($this->intro_video_path) : null;
    }

    public function isLive(): bool
    {
        return $this->status === self::EN_VIVO;
    }

    public function isReplayAvailable(): bool
    {
        return $this->status === self::FINALIZADO && filled($this->replay_url);
    }

    public function connectionStatusLabel(): string
    {
        if ($this->status === self::BORRADOR) {
            return $this->scheduled_at ? __('PROGRAMADO') : __('VISTA PREVIA');
        }

        if (! $this->isLive()) {
            return __('REPLAY');
        }

        return match ($this->signal_status) {
            'online' => __('EN VIVO'),
            'reconnecting' => __('RECONECTANDO'),
            default => __('SIN SEÑAL'),
        };
    }

    public function playbackUrl(): string
    {
        if ($this->isReplayAvailable()) {
            return $this->replay_url;
        }

        return $this->stream_origin === 'merkamigo' && filled($this->stream_path)
            ? $this->hlsUrl()
            : $this->stream_url;
    }

    /** @return array<int, array{provider: string, url: string}> */
    public function destinationsList(): array
    {
        $destinations = collect($this->destinations ?? [])
            ->filter(fn (mixed $destination): bool => is_array($destination)
                && filled($destination['provider'] ?? null)
                && filled($destination['url'] ?? null))
            ->map(fn (array $destination): array => [
                'provider' => (string) $destination['provider'],
                'url' => (string) $destination['url'],
            ])
            ->values()
            ->all();

        return $destinations ?: [[
            'provider' => $this->provider,
            'url' => $this->stream_url,
        ]];
    }

    public static function providerLabel(string $provider): string
    {
        return match ($provider) {
            'youtube' => 'YouTube Live',
            'vimeo' => 'Vimeo',
            'facebook' => 'Facebook Live',
            'tiktok' => 'TikTok LIVE',
            default => __('Enlace externo'),
        };
    }

    public function embedUrl(): ?string
    {
        if ($this->stream_origin === 'merkamigo') {
            return null;
        }

        $url = $this->playbackUrl();

        if ($this->provider === 'youtube') {
            preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|live/|embed/))([\w-]{6,})~', $url, $matches);

            return isset($matches[1]) ? "https://www.youtube.com/embed/{$matches[1]}" : null;
        }

        if ($this->provider === 'vimeo') {
            preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $matches);

            return isset($matches[1]) ? "https://player.vimeo.com/video/{$matches[1]}" : null;
        }

        return null;
    }

    public function currentViewersCount(): int
    {
        return $this->views()->where('last_seen_at', '>=', now()->subSeconds(45))->count();
    }
}
