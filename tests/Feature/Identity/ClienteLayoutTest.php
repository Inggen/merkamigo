<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pedido del usuario (2026-10-06): "Toda las secciones que tienen los
 * clientes deben verse como el site principal, con el header normal...
 * esto así como está me confunde con el modo emprendedor." Antes de este
 * cambio, cualquier página sin `#[Layout(...)]` propio (el layout por
 * defecto de Livewire es `layouts::app`, sin override en este proyecto —
 * ver `resources/views/layouts/app.blade.php`) mostraba el panel con
 * sidebar sin importar la experiencia del usuario, igual para un cliente
 * que para un emprendedor. Ahora ese layout compartido elige según
 * `experience`: un cliente ve el header normal del sitio
 * (`x-layouts::cliente`), un emprendedor sigue viendo su panel.
 */
class ClienteLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cliente_sees_the_normal_site_header_not_the_sidebar_panel(): void
    {
        $user = User::factory()->create(['experience' => 'cliente']);

        $response = $this->actingAs($user)->get(route('clientes.actividad'));

        $response->assertOk();
        $response->assertDontSee('<ui-sidebar', false);
        // El mismo header de Vitrinas/Comunidad/Eventos/Merkapuntos.
        $response->assertSee(route('explorar'), false);
        $response->assertSee('href="'.route('home').'"', false);
    }

    public function test_an_emprendedor_still_sees_the_sidebar_panel_on_the_same_shared_page(): void
    {
        $user = User::factory()->create(['experience' => 'emprendedor']);

        $response = $this->actingAs($user)->get(route('clientes.actividad'));

        $response->assertOk();
        $response->assertSee('<ui-sidebar', false);
    }

    public function test_a_user_without_an_experience_yet_still_sees_the_sidebar_panel(): void
    {
        $user = User::factory()->create(['experience' => null]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('<ui-sidebar', false);
    }

    public function test_the_favorites_page_also_uses_the_normal_header_for_a_cliente(): void
    {
        $user = User::factory()->create(['experience' => 'cliente']);

        $response = $this->actingAs($user)->get(route('clientes.favoritos'));

        $response->assertOk();
        $response->assertDontSee('<ui-sidebar', false);
    }
}
