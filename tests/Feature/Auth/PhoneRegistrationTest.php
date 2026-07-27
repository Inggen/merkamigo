<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 0.5 del TODO: registro por correo y/o teléfono.
 */
class PhoneRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_with_only_a_phone_number(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Ana Emprendedora',
            'phone' => '+573001234567',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'phone' => '+573001234567',
            'email' => null,
        ]);
    }

    public function test_registration_fails_without_email_or_phone(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Sin Contacto',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['email', 'phone']);
        $this->assertGuest();
    }

    public function test_a_user_can_log_in_with_a_phone_number(): void
    {
        $user = User::factory()->create([
            'email' => null,
            'phone' => '+573007654321',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => '+573007654321',
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }
}
