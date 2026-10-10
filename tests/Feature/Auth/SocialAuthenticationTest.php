<?php

namespace Tests\Feature\Auth;

use App\Domain\Identity\Notifications\WelcomeToMerkamigo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_registration_show_google_and_facebook(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Google')
            ->assertSee('Facebook');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Google')
            ->assertSee('Facebook');
    }

    public function test_existing_user_can_log_in_with_google_and_the_account_is_linked(): void
    {
        $user = User::factory()->create([
            'email' => 'persona@example.com',
            'email_verified_at' => null,
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'email' => 'persona@example.com',
        ]));

        $this->get(route('auth.social.callback', 'google'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-123',
        ]);
    }

    public function test_new_social_user_must_add_phone_and_accept_terms(): void
    {
        Notification::fake();
        Socialite::fake('facebook', SocialiteUser::fake([
            'id' => 'facebook-456',
            'name' => 'Emprendedora Local',
            'email' => 'emprendedora@example.com',
        ]));

        $this->get(route('auth.social.callback', 'facebook'))
            ->assertRedirect(route('auth.social.complete'));

        $this->post(route('auth.social.store'), [
            'name' => 'Emprendedora Local',
        ])->assertSessionHasErrors(['phone', 'terms']);

        $this->post(route('auth.social.store'), [
            'name' => 'Emprendedora Local',
            'phone' => '+573001234567',
            'terms' => '1',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'emprendedora@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('+573001234567', $user->phone);
        $this->assertNotNull($user->terms_accepted_at);
        Notification::assertSentTo($user, WelcomeToMerkamigo::class);
    }
}
