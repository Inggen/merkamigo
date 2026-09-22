<?php

namespace App\Domain\Social\Actions;

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Social\Models\LiveStream;
use App\Domain\Social\Models\LiveStreamMessage;
use App\Domain\Social\Models\LiveStreamReaction;
use App\Models\User;
use App\Support\Validation\Rules\NoLinks;
use Illuminate\Http\Request;

class EngageWithLiveStream
{
    public const REACTION_EMOJIS = [
        'like' => '👍',
        'laugh' => '😂',
        'wow' => '😮',
        'celebrate' => '🎉',
        'thanks' => '🙏',
    ];

    public function heartbeat(LiveStream $stream, Request $request, ?User $user): string
    {
        $visitorHash = $this->visitorHash($request, $user);

        $stream->views()->updateOrCreate(
            ['visitor_hash' => $visitorHash],
            ['user_id' => $user?->id, 'last_seen_at' => now()],
        );

        app(RegisterAnalyticsEvent::class)->handle($stream->business, AnalyticsEvent::LIVE_VIEW, $stream, $request);

        return $visitorHash;
    }

    public function toggleReaction(LiveStream $stream, Request $request, ?User $user): bool
    {
        $visitorHash = $this->visitorHash($request, $user);
        $reaction = $stream->reactions()->where('visitor_hash', $visitorHash)->where('type', 'heart')->first();

        if ($reaction) {
            $reaction->delete();

            return false;
        }

        $stream->reactions()->create([
            'user_id' => $user?->id,
            'visitor_hash' => $visitorHash,
            'type' => 'heart',
        ]);

        return true;
    }

    public function react(LiveStream $stream, string $type, Request $request, ?User $user): LiveStreamReaction
    {
        abort_unless(isset(self::REACTION_EMOJIS[$type]), 422, __('Reacción no válida.'));
        abort_unless($stream->isLive(), 422, __('La transmisión ya terminó.'));

        return $stream->reactions()->create([
            'user_id' => $user?->id,
            'visitor_hash' => $this->visitorHash($request, $user),
            'type' => $type,
        ]);
    }

    public function message(LiveStream $stream, string $body, User $user): LiveStreamMessage
    {
        $validated = validator(['body' => $body], [
            'body' => ['required', 'string', 'max:280', new NoLinks],
        ])->validate();

        abort_unless($stream->isLive(), 422, __('El chat se cerró porque la transmisión terminó.'));

        return $stream->messages()->create([
            'user_id' => $user->id,
            'body' => $validated['body'],
        ]);
    }

    private function visitorHash(Request $request, ?User $user): string
    {
        $identity = $user ? "user:{$user->id}" : 'session:'.$request->session()->getId();

        return hash_hmac('sha256', $identity, (string) config('app.key'));
    }
}
