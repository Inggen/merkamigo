<?php

namespace Tests\Feature\Api;

use App\Domain\Identity\Notifications\WelcomeToMerkamigo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_registration_requires_email_and_phone(): void
    {
        $this->postJson(route('api.v1.auth.register'), [
            'name' => 'Usuario API',
            'email' => 'api@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ])->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['details' => ['phone']]]);

        $this->postJson(route('api.v1.auth.register'), [
            'name' => 'Usuario API',
            'phone' => '+573001234580',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ])->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['details' => ['email']]]);
    }

    public function test_api_registration_accepts_both_contact_channels(): void
    {
        Notification::fake();

        $this->postJson(route('api.v1.auth.register'), [
            'name' => 'Usuario API Completo',
            'email' => 'api-completo@example.com',
            'phone' => '+573001234581',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => true,
        ])->assertCreated()
            ->assertJsonPath('data.user.email', 'api-completo@example.com')
            ->assertJsonPath('data.user.phone', '+573001234581');

        $user = User::where('email', 'api-completo@example.com')->firstOrFail();

        Notification::assertSentTo($user, WelcomeToMerkamigo::class);
    }
}
