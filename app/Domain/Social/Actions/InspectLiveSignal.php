<?php

namespace App\Domain\Social\Actions;

use App\Domain\Social\Models\LiveStream;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class InspectLiveSignal
{
    public function handle(LiveStream $stream): bool
    {
        if (blank($stream->stream_path)) {
            return false;
        }

        try {
            $response = Http::timeout(2)
                ->acceptJson()
                ->get(rtrim(config('services.live_streaming.api_url'), '/').'/v3/paths/list');

            if (! $response->successful()) {
                return false;
            }

            return collect($response->json('items', []))->contains(
                fn (array $path): bool => ($path['name'] ?? null) === $stream->stream_path
                    && (bool) ($path['ready'] ?? false)
            );
        } catch (ConnectionException) {
            return false;
        }
    }
}
