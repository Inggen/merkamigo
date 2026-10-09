<?php

namespace Tests\Feature\Auth;

use App\Domain\Identity\Notifications\WelcomeToMerkamigo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk()
            ->assertSee('Correo electrónico')
            ->assertSee('Teléfono')
            ->assertSee('Usaremos tu correo y teléfono');
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->post(route('front.register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '+573001234567',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'phone' => '+573001234567',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();
        Notification::assertSentTo($user, WelcomeToMerkamigo::class);
    }

    public function test_the_welcome_email_links_to_the_experience_selected_before_registration(): void
    {
        Notification::fake();

        $this->withUnencryptedCookie('experience', 'emprendedor')
            ->post(route('front.register.store'), [
                'name' => 'Ana Emprendedora',
                'email' => 'ana@example.com',
                'phone' => '+573001234568',
                'password' => 'password',
                'password_confirmation' => 'password',
                'terms' => '1',
            ]);

        $user = User::where('email', 'ana@example.com')->firstOrFail();

        Notification::assertSentTo($user, WelcomeToMerkamigo::class, function (WelcomeToMerkamigo $notification, array $channels) use ($user): bool {
            $mail = $notification->toMail($user);

            return $channels === ['mail']
                && $mail->subject === '¡Bienvenido a Merkamigo!'
                && $mail->view === [
                    'html' => 'mail.identity.welcome',
                    'text' => 'mail.identity.welcome-text',
                ]
                && $mail->viewData['isEntrepreneur'] === true
                && $mail->viewData['primaryUrl'] === route('emprendedores.crear-vitrina')
                && $mail->viewData['secondaryUrl'] === route('emprendedores.home');
        });
    }

    public function test_the_client_welcome_email_invites_the_user_to_explore(): void
    {
        Notification::fake();

        $this->withUnencryptedCookie('experience', 'cliente')
            ->post(route('front.register.store'), [
                'name' => 'Cliente Local',
                'email' => 'cliente@example.com',
                'phone' => '+573001234569',
                'password' => 'password',
                'password_confirmation' => 'password',
                'terms' => '1',
            ]);

        $user = User::where('email', 'cliente@example.com')->firstOrFail();

        Notification::assertSentTo($user, WelcomeToMerkamigo::class, function (WelcomeToMerkamigo $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            return $mail->viewData['isEntrepreneur'] === false
                && $mail->viewData['primaryUrl'] === route('explorar')
                && $mail->viewData['secondaryUrl'] === route('profile.edit');
        });
    }

    /**
     * 0.6 del TODO: registrar aceptación y versión de documentos legales.
     */
    public function test_registration_records_terms_acceptance_with_version(): void
    {
        $this->post(route('front.register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '+573001234570',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertNotNull($user->terms_accepted_at);
        $this->assertSame(config('legal.terms_version'), $user->terms_version);
    }

    public function test_registration_fails_without_accepting_terms(): void
    {
        $response = $this->post(route('front.register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '+573001234571',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['terms']);
        $this->assertGuest();
    }
}
