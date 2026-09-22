<?php

namespace App\Http\Controllers\Streaming;

use App\Domain\Social\Models\LiveStream;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StreamAuthController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'action' => ['required', 'string'],
            'path' => ['required', 'string', 'max:255'],
            'user' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        $stream = LiveStream::query()->where('stream_path', $validated['path'])->first();

        if (! $stream) {
            return response('', 403);
        }

        if (in_array($validated['action'], ['read', 'playback'], true)) {
            return $stream->status === LiveStream::BORRADOR
                ? response('', 403)
                : response('', 204);
        }

        if ($validated['action'] !== 'publish' || $stream->status === LiveStream::FINALIZADO) {
            return response('', 403);
        }

        $authorized = hash_equals('merkamigo', (string) ($validated['user'] ?? ''))
            && hash_equals((string) $stream->stream_key, (string) ($validated['password'] ?? ''));

        return response('', $authorized ? 204 : 403);
    }
}
