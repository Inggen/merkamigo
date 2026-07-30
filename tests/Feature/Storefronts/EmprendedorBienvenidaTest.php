<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Discovery\Models\Municipality;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * E01/1.6 del TODO: bienvenida del Emprendedor con imagen local
 * administrable cuando hay un municipio preferido.
 */
class EmprendedorBienvenidaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_the_preferred_municipality_cover_when_available(): void
    {
        $municipality = Municipality::create([
            'name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca',
            'is_active' => true, 'cover_path' => 'municipalities/cajica.jpg',
        ]);

        $this->withUnencryptedCookie('municipio', $municipality->slug)
            ->get(route('emprendedores.bienvenida'))
            ->assertOk()
            ->assertSeeHtml($municipality->coverUrl());
    }

    public function test_it_falls_back_to_the_generic_background_without_a_preferred_municipality(): void
    {
        $this->get(route('emprendedores.bienvenida'))
            ->assertOk()
            ->assertSeeHtml(asset('images/backgrounds/fondo-buscador-principal.webp'));
    }

    public function test_an_inactive_or_unknown_municipality_cookie_also_falls_back(): void
    {
        $this->withUnencryptedCookie('municipio', 'no-existe')
            ->get(route('emprendedores.bienvenida'))
            ->assertOk()
            ->assertSeeHtml(asset('images/backgrounds/fondo-buscador-principal.webp'));
    }
}
