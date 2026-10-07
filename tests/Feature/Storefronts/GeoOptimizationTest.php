<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Platform\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Optimización GEO (2026-10-03): visibilidad del sitio para motores de
 * respuesta con IA además del SEO clásico — `llms.txt`, perfiles sociales
 * reales en `sameAs`, y contenido propio por municipio en la Plaza.
 */
class GeoOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_llms_txt_is_served_as_plain_text_with_real_active_municipalities(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        Municipality::create(['name' => 'Inactivo', 'slug' => 'inactivo', 'department' => 'Cundinamarca', 'is_active' => false]);

        $response = $this->get('/llms.txt');

        $response->assertOk();
        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $response->assertSee('# Merkamigo', false);
        $response->assertSee('## Municipios activos', false);
        $response->assertSee($municipality->name);
        $response->assertDontSee('Inactivo');
    }

    public function test_robots_txt_references_llms_txt_and_welcomes_ai_crawlers(): void
    {
        // `public/robots.txt` es un archivo estático: en producción lo
        // sirve el servidor web directo, nunca pasa por el router de
        // Laravel (de ahí leerlo de disco en vez de un `$this->get()`,
        // que daría 404 al no haber ninguna ruta registrada para él).
        $content = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Allow: /llms.txt', $content);
        $this->assertStringContainsString('GPTBot', $content);
        $this->assertStringContainsString('ClaudeBot', $content);
        $this->assertStringContainsString('PerplexityBot', $content);
    }

    public function test_organization_schema_includes_sameas_only_when_social_profiles_are_configured(): void
    {
        SiteSetting::current()->update([
            'social_facebook_url' => null,
            'social_instagram_url' => null,
        ]);

        $response = $this->get(route('home'));
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $schema = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $organization = collect($schema['@graph'])->firstWhere('@type', 'Organization');

        $this->assertArrayNotHasKey('sameAs', $organization);

        SiteSetting::current()->update([
            'social_facebook_url' => 'https://www.facebook.com/merkamigo',
            'social_instagram_url' => 'https://www.instagram.com/merkamigo',
        ]);

        $response = $this->get(route('home'));
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $schema = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $organization = collect($schema['@graph'])->firstWhere('@type', 'Organization');

        $this->assertSame([
            'https://www.facebook.com/merkamigo',
            'https://www.instagram.com/merkamigo',
        ], $organization['sameAs']);
    }

    public function test_the_footer_only_shows_social_icons_with_a_real_configured_profile(): void
    {
        SiteSetting::current()->update([
            'social_facebook_url' => null,
            'social_instagram_url' => null,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('href="https://facebook.com"', false)
            ->assertDontSee('href="https://instagram.com"', false);

        SiteSetting::current()->update(['social_facebook_url' => 'https://www.facebook.com/merkamigo']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="https://www.facebook.com/merkamigo"', false)
            ->assertDontSee('href="https://instagram.com"', false);
    }

    public function test_the_official_merkamigo_social_profiles_are_configured_by_default(): void
    {
        $settings = SiteSetting::current();

        $this->assertSame('https://www.facebook.com/profile.php?id=61592810691144', $settings->social_facebook_url);
        $this->assertSame('https://www.instagram.com/merkamigos/', $settings->social_instagram_url);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="https://www.facebook.com/profile.php?id=61592810691144"', false)
            ->assertSee('href="https://www.instagram.com/merkamigos/"', false);
    }

    public function test_the_plaza_page_shows_the_municipality_description_and_uses_it_as_meta_description(): void
    {
        $municipality = Municipality::create([
            'name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true,
            'description' => 'Cajicá es un municipio de la Sabana Norte de Cundinamarca, conocido por su tradición lechera.',
        ]);

        $this->get(route('buscar', ['municipio' => $municipality->slug]))
            ->assertOk()
            ->assertSee('Cajicá es un municipio de la Sabana Norte de Cundinamarca, conocido por su tradición lechera.')
            ->assertSee('<meta name="description" content="Cajicá es un municipio de la Sabana Norte de Cundinamarca, conocido por su tradición lechera.">', false);
    }

    public function test_the_plaza_page_falls_back_to_a_generic_description_when_the_municipality_has_none(): void
    {
        $municipality = Municipality::create(['name' => 'Sin Descripción', 'slug' => 'sin-descripcion', 'department' => 'Cundinamarca', 'is_active' => true]);

        $this->get(route('buscar', ['municipio' => $municipality->slug]))
            ->assertOk()
            ->assertSee('Explora negocios, productos y servicios locales en Sin Descripción.');
    }
}
