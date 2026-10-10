<?php

namespace App\Support\YouTube;

use App\Domain\Identity\Models\SocialAccount;
use App\Domain\Social\Models\LiveDestination;
use App\Domain\Social\Models\LiveStream;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class YouTubeLiveClient
{
    public const SCOPE = 'https://www.googleapis.com/auth/youtube.force-ssl';

    public function createDestination(LiveStream $stream, SocialAccount $account): LiveDestination
    {
        $accessToken = $this->accessToken($account);
        $scheduledAt = $stream->scheduled_at?->isFuture()
            ? $stream->scheduled_at
            : now()->addMinutes(5);

        $broadcast = $this->api($accessToken)
            ->withQueryParameters(['part' => 'snippet,status,contentDetails'])
            ->post('https://www.googleapis.com/youtube/v3/liveBroadcasts', [
                'snippet' => [
                    'title' => $stream->title,
                    'description' => (string) $stream->description,
                    'scheduledStartTime' => $scheduledAt->toIso8601String(),
                ],
                'status' => [
                    'privacyStatus' => 'unlisted',
                    'selfDeclaredMadeForKids' => false,
                ],
                'contentDetails' => [
                    'enableAutoStart' => true,
                    'enableAutoStop' => true,
                ],
            ])->throw()->json();

        $youtubeStream = $this->api($accessToken)
            ->withQueryParameters(['part' => 'snippet,cdn'])
            ->post('https://www.googleapis.com/youtube/v3/liveStreams', [
                'snippet' => ['title' => $stream->title],
                'cdn' => [
                    'ingestionType' => 'rtmp',
                    'resolution' => '1080p',
                    'frameRate' => '30fps',
                ],
            ])->throw()->json();

        $broadcastId = data_get($broadcast, 'id');
        $youtubeStreamId = data_get($youtubeStream, 'id');
        $ingestionAddress = data_get($youtubeStream, 'cdn.ingestionInfo.ingestionAddress');
        $streamName = data_get($youtubeStream, 'cdn.ingestionInfo.streamName');

        if (! $broadcastId || ! $youtubeStreamId || ! $ingestionAddress || ! $streamName) {
            throw new RuntimeException(__('YouTube no devolvió los datos RTMP de la transmisión.'));
        }

        $this->api($accessToken)
            ->withQueryParameters([
                'id' => $broadcastId,
                'streamId' => $youtubeStreamId,
                'part' => 'id,contentDetails',
            ])
            ->post('https://www.googleapis.com/youtube/v3/liveBroadcasts/bind')
            ->throw();

        return $stream->streamingDestinations()->updateOrCreate(
            ['provider' => 'youtube'],
            [
                'name' => __('YouTube · :title', ['title' => $stream->title]),
                'rtmp_url' => $ingestionAddress,
                'stream_key' => $streamName,
                'is_enabled' => true,
                'status' => 'disconnected',
                'last_error' => null,
            ],
        );
    }

    private function accessToken(SocialAccount $account): string
    {
        if (filled($account->access_token) && $account->token_expires_at?->isFuture()) {
            return $account->access_token;
        }

        if (blank($account->refresh_token)) {
            throw new RuntimeException(__('Vuelve a autorizar tu canal de YouTube.'));
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ])->throw()->json();

        $account->update([
            'access_token' => $response['access_token'],
            'token_expires_at' => now()->addSeconds((int) ($response['expires_in'] ?? 3600)),
        ]);

        return $response['access_token'];
    }

    private function api(string $accessToken): PendingRequest
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->asJson();
    }
}
