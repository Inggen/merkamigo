<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Actions\RegisterUser;
use App\Domain\Identity\Models\SocialAccount;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'facebook'];

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureSupported($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider, Request $request): RedirectResponse
    {
        $this->ensureSupported($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable) {
            return to_route('login')->withErrors([
                'social' => __('No pudimos completar el ingreso con :provider. Inténtalo nuevamente.', [
                    'provider' => Str::headline($provider),
                ]),
            ]);
        }

        $email = Str::lower(trim((string) $socialUser->getEmail()));

        if ($email === '') {
            return to_route('login')->withErrors([
                'social' => __('La cuenta de :provider no compartió un correo electrónico.', [
                    'provider' => Str::headline($provider),
                ]),
            ]);
        }

        $account = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_user_id', (string) $socialUser->getId())
            ->first();

        $user = $account?->user ?? User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            $user->socialAccounts()->updateOrCreate(
                ['provider' => $provider],
                ['provider_user_id' => (string) $socialUser->getId(), 'email' => $email],
            );
            $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        $request->session()->put('social_registration', [
            'provider' => $provider,
            'provider_user_id' => (string) $socialUser->getId(),
            'name' => (string) ($socialUser->getName() ?: $socialUser->getNickname() ?: ''),
            'email' => $email,
        ]);

        return to_route('auth.social.complete');
    }

    public function complete(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('social_registration')) {
            return to_route('register');
        }

        return view('pages::auth.social-complete', [
            'profile' => $request->session()->get('social_registration'),
        ]);
    }

    public function store(Request $request, RegisterUser $registerUser): RedirectResponse
    {
        $profile = $request->session()->get('social_registration');

        if (! is_array($profile)) {
            return to_route('register');
        }

        $password = Str::password(40);
        $user = $registerUser->create([
            'name' => $request->string('name')->toString(),
            'email' => (string) $profile['email'],
            'phone' => $request->string('phone')->toString(),
            'password' => $password,
            'password_confirmation' => $password,
            'terms' => $request->input('terms'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();
        $user->socialAccounts()->create([
            'provider' => (string) $profile['provider'],
            'provider_user_id' => (string) $profile['provider_user_id'],
            'email' => (string) $profile['email'],
        ]);

        Auth::login($user, true);
        $request->session()->forget('social_registration');
        $request->session()->regenerate();
        $request->session()->put('just_registered', true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function ensureSupported(string $provider): void
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
    }
}
