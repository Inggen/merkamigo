<?php

namespace App\Http\Controllers\Streaming;

use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Actions\ManageLiveStream;
use App\Domain\Social\Models\LiveStream;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Throwable;

class WhipProxyController extends Controller
{
    public function publish(Business $business, LiveStream $liveStream, Request $request): Response
    {
        $this->authorizeStream($business, $liveStream);
        abort_if($liveStream->status === LiveStream::FINALIZADO, 422);
        abort_unless(str_contains((string) $request->header('Content-Type'), 'application/sdp'), 415);
        abort_if(strlen($request->getContent()) > 1_000_000, 413);

        try {
            $upstream = Http::timeout(20)
                ->withBasicAuth('merkamigo', (string) $liveStream->stream_key)
                ->withBody($request->getContent(), 'application/sdp')
                ->send('POST', $this->whipUrl($liveStream));
        } catch (ConnectionException) {
            return response(__('El servidor de transmisión no está disponible.'), 503);
        }

        if ($upstream->status() === 201 && $liveStream->status === LiveStream::BORRADOR) {
            app(ManageLiveStream::class)->start($liveStream, $request->user());
        }

        $response = response($upstream->body(), $upstream->status())
            ->header('Content-Type', $upstream->header('Content-Type', 'application/sdp'));

        if ($upstream->header('Location')) {
            $response->header('X-Whip-Session', Crypt::encryptString(
                $this->absoluteSessionUrl($upstream->header('Location')),
            ));
        }

        return $response;
    }

    public function destroy(Business $business, LiveStream $liveStream, Request $request): Response
    {
        $this->authorizeStream($business, $liveStream);

        try {
            $url = Crypt::decryptString((string) $request->header('X-Whip-Session'));
        } catch (Throwable) {
            abort(422, __('La sesión de transmisión no es válida.'));
        }

        abort_unless(str_starts_with($url, rtrim($this->internalUrl(), '/').'/'), 422);

        try {
            $upstream = Http::timeout(10)->delete($url);
        } catch (ConnectionException) {
            $this->finishLive($liveStream, $request);

            return response('', 204);
        }

        if (in_array($upstream->status(), [200, 204, 404], true)) {
            $this->finishLive($liveStream, $request);
        }

        return response('', in_array($upstream->status(), [200, 204, 404], true) ? 204 : $upstream->status());
    }

    private function authorizeStream(Business $business, LiveStream $liveStream): void
    {
        abort_unless($liveStream->business_id === $business->id, 404);
        $this->authorize('update', $business);
    }

    private function whipUrl(LiveStream $liveStream): string
    {
        return rtrim($this->internalUrl(), '/').'/'.$liveStream->stream_path.'/whip';
    }

    private function internalUrl(): string
    {
        return config('services.live_streaming.webrtc_internal_url');
    }

    private function absoluteSessionUrl(string $location): string
    {
        if (str_starts_with($location, '/')) {
            return rtrim($this->internalUrl(), '/').$location;
        }

        abort_unless(str_starts_with($location, rtrim($this->internalUrl(), '/').'/'), 502);

        return $location;
    }

    private function finishLive(LiveStream $liveStream, Request $request): void
    {
        if ($liveStream->isLive()) {
            app(ManageLiveStream::class)->end($liveStream, null, $request->user());
        }
    }
}
