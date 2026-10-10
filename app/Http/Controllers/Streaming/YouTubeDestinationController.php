<?php

namespace App\Http\Controllers\Streaming;

use App\Domain\Businesses\Models\Business;
use App\Domain\Identity\Models\SocialAccount;
use App\Domain\Social\Models\LiveStream;
use App\Http\Controllers\Controller;
use App\Support\YouTube\YouTubeLiveClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;
use Throwable;

class YouTubeDestinationController extends Controller
{
    public function redirect(Business $business, LiveStream $liveStream, Request $request, YouTubeLiveClient $youtube): RedirectResponse
    {
        $this->authorizeStream($business, $liveStream, $request);

        $account = $request->user()->socialAccounts()
            ->where('provider', 'google')
            ->first();

        if ($account && in_array(YouTubeLiveClient::SCOPE, $account->scopes ?? [], true) && filled($account->refresh_token)) {
            return $this->createDestination($business, $liveStream, $account, $youtube);
        }

        $request->session()->put('youtube_live_intent', [
            'business_id' => $business->id,
            'live_stream_id' => $liveStream->id,
        ]);

        return Socialite::driver('google')
            ->redirectUrl(route('auth.youtube.callback'))
            ->scopes([YouTubeLiveClient::SCOPE])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
            ])
            ->redirect();
    }

    public function callback(Request $request, YouTubeLiveClient $youtube): RedirectResponse
    {
        $intent = $request->session()->pull('youtube_live_intent');

        if (! is_array($intent)) {
            return to_route('emprendedores.home')->with('youtube_error', __('La autorización de YouTube expiró. Inténtalo nuevamente.'));
        }

        $business = Business::findOrFail($intent['business_id']);
        $liveStream = LiveStream::findOrFail($intent['live_stream_id']);
        $this->authorizeStream($business, $liveStream, $request);

        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(route('auth.youtube.callback'))
                ->user();

            $linkedToAnotherUser = SocialAccount::query()
                ->where('provider', 'google')
                ->where('provider_user_id', (string) $googleUser->getId())
                ->where('user_id', '!=', $request->user()->id)
                ->exists();

            if ($linkedToAnotherUser) {
                throw new RuntimeException(__('Ese canal de Google ya está vinculado a otra cuenta de Merkamigo.'));
            }

            $existing = $request->user()->socialAccounts()->where('provider', 'google')->first();
            $account = $request->user()->socialAccounts()->updateOrCreate(
                ['provider' => 'google'],
                [
                    'provider_user_id' => (string) $googleUser->getId(),
                    'email' => $googleUser->getEmail(),
                    'access_token' => $googleUser->token,
                    'refresh_token' => $googleUser->refreshToken ?: $existing?->refresh_token,
                    'token_expires_at' => now()->addSeconds((int) ($googleUser->expiresIn ?: 3600)),
                    'scopes' => array_values(array_unique([
                        ...($existing?->scopes ?? []),
                        ...($googleUser->approvedScopes ?? []),
                        YouTubeLiveClient::SCOPE,
                    ])),
                ],
            );

            return $this->createDestination($business, $liveStream, $account, $youtube);
        } catch (Throwable $exception) {
            report($exception);

            return to_route('emprendedores.negocios.lives', $business)
                ->with('youtube_error', $this->friendlyError($exception));
        }
    }

    private function createDestination(Business $business, LiveStream $liveStream, SocialAccount $account, YouTubeLiveClient $youtube): RedirectResponse
    {
        try {
            $youtube->createDestination($liveStream, $account);

            return to_route('emprendedores.negocios.lives', $business)
                ->with('youtube_success', __('YouTube quedó conectado. Al iniciar el Live, Merkamigo enviará la señal automáticamente.'));
        } catch (Throwable $exception) {
            report($exception);

            return to_route('emprendedores.negocios.lives', $business)
                ->with('youtube_error', $this->friendlyError($exception));
        }
    }

    private function authorizeStream(Business $business, LiveStream $liveStream, Request $request): void
    {
        abort_unless($liveStream->business_id === $business->id, 404);
        abort_if($liveStream->status === LiveStream::FINALIZADO, 422);

        setPermissionsTeamId($business->id);
        $request->user()->unsetRelation('roles');
        $this->authorize('update', $business);
    }

    private function friendlyError(Throwable $exception): string
    {
        if ($exception instanceof RequestException) {
            $reason = data_get($exception->response->json(), 'error.errors.0.reason');

            return match ($reason) {
                'liveStreamingNotEnabled' => __('Activa las transmisiones en vivo en tu canal de YouTube y vuelve a intentarlo.'),
                'insufficientPermissions' => __('Google no concedió el permiso necesario para administrar transmisiones en YouTube.'),
                default => __('YouTube no pudo preparar la transmisión. Revisa tu canal e inténtalo nuevamente.'),
            };
        }

        return $exception instanceof RuntimeException
            ? $exception->getMessage()
            : __('No pudimos conectar YouTube. Inténtalo nuevamente.');
    }
}
