<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 0.2/0.2.1 del TODO: cambiar de experiencia no cierra sesión ni duplica
 * cuenta, y se recuerda entre visitas.
 */
class SwitchExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_switch_experience_without_losing_the_session(): void
    {
        $user = User::factory()->create(['experience' => null]);
        $this->actingAs($user);

        $response = $this->post(route('experience.update'), ['experience' => 'emprendedor']);

        $response->assertRedirect(route('emprendedores.home'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('emprendedor', $user->fresh()->experience);
    }

    public function test_a_user_can_switch_back_and_forth_between_experiences(): void
    {
        $user = User::factory()->create(['experience' => 'emprendedor']);
        $this->actingAs($user);

        $this->post(route('experience.update'), ['experience' => 'cliente'])
            ->assertRedirect(route('clientes.home'));

        $this->assertSame('cliente', $user->fresh()->experience);
    }

    public function test_dashboard_redirects_to_the_right_home_once_an_experience_is_set(): void
    {
        $user = User::factory()->create(['experience' => 'cliente']);
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertRedirect(route('clientes.home'));
    }

    public function test_dashboard_shows_the_picker_when_no_experience_is_set(): void
    {
        $user = User::factory()->create(['experience' => null]);
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_a_guest_choosing_an_experience_is_sent_to_register_and_it_is_remembered(): void
    {
        $response = $this->post(route('experience.update'), ['experience' => 'emprendedor']);

        $response->assertRedirect(route('emprendedores.bienvenida'));

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === 'experience');

        $this->assertNotNull($cookie);

        $this->withUnencryptedCookie('experience', $cookie->getValue())
            ->post(route('register.store'), [
                'name' => 'Nueva Emprendedora',
                'email' => 'nueva@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'terms' => '1',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'nueva@example.com',
            'experience' => 'emprendedor',
        ]);
    }
}
