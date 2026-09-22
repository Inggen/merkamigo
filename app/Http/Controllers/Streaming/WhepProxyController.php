<?php

namespace App\Http\Controllers\Streaming;

use App\Domain\Social\Models\LiveStream;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Throwable;

// Lectura WebRTC (WHEP) del Live: los navegadores publican VP8/Opus (WebRTC
// nativo) y el muxer HLS de MediaMTX solo soporta H.264, así que la señal
// en vivo se consume por WHEP — mismo códec, sin transcodificación. El HLS
// queda para la repetición y para players sin WebRTC.
class WhepProxyController extends Controller
{
    public function publish(LiveStream $liveStream, Request $request): Response
    {
        abort_if($liveStream->status === LiveStream::BORRADOR, 404);
        abort_if($liveStream->stream_origin !== 'merkamigo' || blank($liveStream->stream_path), 404);
        abort_unless(str_contains((string) $request->header('Content-Type'), 'application/sdp'), 415);
        abort_if(strlen($request->getContent()) > 1_000_000, 413);

        try {
            $upstream = Http::timeout(20)
                ->withBody($request->getContent(), 'application/sdp')
                ->send('POST', $this->whepUrl($liveStream));
        } catch (ConnectionException) {
            return response(__('La señal del Live no está disponible.'), 503);
        }

        $response = response($upstream->body(), $upstream->status())
            ->header('Content-Type', $upstream->header('Content-Type', 'application/sdp'));

        if ($upstream->header('Location')) {
            $response->header('X-Whep-Session', Crypt::encryptString(
                $this->absoluteSessionUrl($upstream->header('Location')),
            ));
        }

        return $response;
    }

    public function destroy(LiveStream $liveStream, Request $request): Response
    {
        try {
            $url = Crypt::decryptString((string) $request->header('X-Whep-Session'));
        } catch (Throwable) {
            abort(422, __('La sesión de visualización no es válida.'));
        }

        abort_unless(str_starts_with($url, rtrim($this->internalUrl(), '/').'/'), 422);

        try {
            $upstream = Http::timeout(10)->delete($url);
        } catch (ConnectionException) {
            return response('', 204);
        }

        return response('', in_array($upstream->status(), [200, 204, 404], true) ? 204 : $upstream->status());
    }

    private function whepUrl(LiveStream $liveStream): string
    {
        return rtrim($this->internalUrl(), '/').'/'.$liveStream->stream_path.'/whep';
    }

    private function internalUrl(): string
    {
        return rtrim(config('services.live_streaming.webrtc_internal_url'), '/');
    }

    private function absoluteSessionUrl(string $location): string
    {
        if (str_starts_with($location, '/')) {
            return rtrim($this->internalUrl(), '/').$location;
        }

        abort_unless(str_starts_with($location, rtrim($this->internalUrl(), '/').'/'), 502);

        return $location;
    }
}
