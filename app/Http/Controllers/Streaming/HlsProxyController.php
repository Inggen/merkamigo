<?php

namespace App\Http\Controllers\Streaming;

use App\Domain\Social\Models\LiveStream;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class HlsProxyController extends Controller
{
    public function __invoke(LiveStream $liveStream, string $asset, Request $request): Response
    {
        abort_if($liveStream->status === LiveStream::BORRADOR, 404);
        abort_if(blank($liveStream->stream_path) || str_contains($asset, '..'), 404);

        $url = rtrim(config('services.live_streaming.hls_internal_url'), '/')
            .'/'.$liveStream->stream_path.'/'.$asset;

        try {
            $upstream = Http::timeout(20)->get($url, $request->query());
        } catch (ConnectionException) {
            return response(__('La señal del Live no está disponible.'), 503);
        }

        return response($upstream->body(), $upstream->status())
            ->header('Content-Type', $upstream->header('Content-Type', $this->contentType($asset)))
            ->header('Cache-Control', str_ends_with($asset, '.m3u8') ? 'no-store' : 'public, max-age=30');
    }

    private function contentType(string $asset): string
    {
        return match (true) {
            str_ends_with($asset, '.m3u8') => 'application/vnd.apple.mpegurl',
            str_ends_with($asset, '.mp4'), str_ends_with($asset, '.m4s') => 'video/mp4',
            str_ends_with($asset, '.ts') => 'video/mp2t',
            default => 'application/octet-stream',
        };
    }
}
