<?php

namespace App\Domain\Social\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Social\Models\LiveStream;
use App\Domain\Social\Notifications\LiveStarted;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManageLiveStream
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Business $business, array $data, User $actor): LiveStream
    {
        $validated = validator($data, [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'date', 'after_or_equal:now'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'product_ids' => ['required', 'array', 'min:1', 'max:12'],
            'product_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where('business_id', $business->id)->where('status', 'publicado'),
            ],
        ])->validate();

        return DB::transaction(function () use ($business, $validated, $actor) {
            $streamPath = 'live/'.Str::uuid();

            $stream = $business->liveStreams()->create([
                'user_id' => $actor->id,
                'title' => $validated['title'],
                'slug' => $this->uniqueSlug($validated['title']),
                'description' => $validated['description'] ?? null,
                'stream_path' => $streamPath,
                'stream_key' => Str::random(48),
                'stream_origin' => 'merkamigo',
                'signal_status' => 'offline',
                'provider' => 'externo',
                'stream_url' => rtrim(config('services.live_streaming.hls_url'), '/').'/'.$streamPath.'/index.m3u8',
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'status' => LiveStream::BORRADOR,
            ]);

            if (isset($validated['cover'])) {
                $stream->update([
                    'cover_path' => $validated['cover']->store("live-covers/{$stream->id}", 'public'),
                ]);
            }

            $stream->products()->sync(collect($validated['product_ids'])
                ->values()
                ->mapWithKeys(fn (int $id, int $position) => [$id => ['position' => $position]])
                ->all());

            app(RecordAuditLog::class)->handle($actor, 'live.created', $stream, [
                'business_id' => $business->id,
            ]);

            return $stream->load('products');
        });
    }

    public function regenerateStreamKey(LiveStream $stream, User $actor): LiveStream
    {
        if ($stream->status !== LiveStream::BORRADOR) {
            throw ValidationException::withMessages(['live' => __('La clave solo puede regenerarse antes de iniciar el Live.')]);
        }

        $stream->forceFill(['stream_key' => Str::random(48)])->save();
        app(RecordAuditLog::class)->handle($actor, 'live.stream_key_regenerated', $stream);

        return $stream->refresh();
    }

    public function start(LiveStream $stream, User $actor): LiveStream
    {
        if ($stream->status !== LiveStream::BORRADOR) {
            throw ValidationException::withMessages(['live' => __('Solo puedes iniciar una transmisión en borrador.')]);
        }

        $stream->forceFill([
            'status' => LiveStream::EN_VIVO,
            'signal_status' => 'online',
            'started_at' => now(),
            'ended_at' => null,
            'pinned_product_id' => null,
        ])->save();

        $followers = $stream->business->follows()->with('user')->get()->pluck('user')->filter();

        if ($followers->isNotEmpty()) {
            Notification::send($followers, new LiveStarted($stream));
        }

        app(RecordAuditLog::class)->handle($actor, 'live.started', $stream);

        return $stream->refresh();
    }

    public function pinProduct(LiveStream $stream, ?int $productId, User $actor): LiveStream
    {
        if ($productId !== null && ! $stream->products()->whereKey($productId)->exists()) {
            throw ValidationException::withMessages(['product' => __('Ese producto no pertenece a esta transmisión.')]);
        }

        if ($stream->pinned_product_id === $productId) {
            return $stream;
        }

        $elapsedSeconds = $stream->started_at ? $stream->started_at->diffInSeconds(now()) : 0;

        if ($stream->pinned_product_id) {
            $stream->productEvents()->create([
                'product_id' => $stream->pinned_product_id,
                'action' => 'unfeatured',
                'elapsed_seconds' => $elapsedSeconds,
                'created_by' => $actor->id,
            ]);
        }

        $stream->update(['pinned_product_id' => $productId]);

        if ($productId) {
            $stream->productEvents()->create([
                'product_id' => $productId,
                'action' => 'featured',
                'elapsed_seconds' => $elapsedSeconds,
                'created_by' => $actor->id,
            ]);
        }

        app(RecordAuditLog::class)->handle($actor, 'live.product_pinned', $stream, [
            'product_id' => $productId,
        ]);

        return $stream->refresh();
    }

    public function end(LiveStream $stream, ?string $replayUrl, User $actor): LiveStream
    {
        if (! $stream->isLive()) {
            throw ValidationException::withMessages(['live' => __('Esta transmisión no está en vivo.')]);
        }

        $validated = validator(['replay_url' => $replayUrl], [
            'replay_url' => ['nullable', 'url:https', 'max:2000'],
        ])->validate();

        $stream->forceFill([
            'status' => LiveStream::FINALIZADO,
            'signal_status' => 'offline',
            'ended_at' => now(),
            'replay_url' => $validated['replay_url'] ?? null,
            'intro_video_active' => false,
        ])->save();

        $stream->polls()->whereNull('closed_at')->update(['closed_at' => now()]);

        app(RecordAuditLog::class)->handle($actor, 'live.ended', $stream);

        return $stream->refresh();
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'live';
        $slug = $base;
        $suffix = 2;

        while (LiveStream::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
