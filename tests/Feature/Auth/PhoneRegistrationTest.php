<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El registro exige ambos canales de contacto, aunque el inicio de sesión
 * continúa aceptando teléfono para las cuentas existentes.
 */
class PhoneRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_an_email_even_when_a_phone_is_present(): void
    {
        $response = $this->post(route('front.register.store'), [
            'name' => 'Ana Emprendedora',
            'phone' => '+573001234567',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['phone' => '+573001234567']);
    }

    public function test_registration_requires_a_phone_even_when_an_email_is_present(): void
    {
        $response = $this->post(route('front.register.store'), [
            'name' => 'Sin Teléfono',
            'email' => 'sin-telefono@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $response->assertSessionHasErrors(['phone']);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'sin-telefono@example.com']);
    }

    public function test_a_user_can_log_in_with_a_phone_number(): void
    {
        $user = User::factory()->create([
            'email' => null,
            'phone' => '+573007654321',
        ]);

        $response = $this->post(route('front.login.store'), [
            'email' => '+573007654321',
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }
}
