<?php

namespace Tests\Feature\Platform;

use App\Domain\Platform\Models\SiteSetting;
use App\Filament\Pages\SiteSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Js;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function assignPlatformRole(User $user, string $role): void
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');
        $user->assignRole(Role::findOrCreate($role, 'web'));

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');
    }

    public function test_without_saved_settings_the_default_share_image_is_null(): void
    {
        $this->assertNull(SiteSetting::current()->defaultShareImageUrl());
    }

    public function test_an_admin_can_upload_the_default_share_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(SiteSettings::class)
            ->fillForm([
                'default_share_image_path' => UploadedFile::fake()->image('share.jpg'),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = SiteSetting::query()->firstOrFail();

        $this->assertNotNull($setting->default_share_image_path);
        $this->assertNotNull($setting->defaultShareImageUrl());
        Storage::disk('public')->assertExists($setting->default_share_image_path);
    }

    public function test_an_admin_can_upload_the_branding_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(SiteSettings::class)
            ->fillForm([
                'logo_path' => UploadedFile::fake()->image('logo.png'),
                'logo_mono_path' => UploadedFile::fake()->image('logo-mono.png'),
                'apple_touch_icon_path' => UploadedFile::fake()->image('apple-touch-icon.png'),
                'login_background_path' => UploadedFile::fake()->image('login-bg.png'),
                'footer_background_path' => UploadedFile::fake()->image('footer-bg.jpg'),
                'main_search_background_path' => UploadedFile::fake()->image('search-bg.jpg'),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = SiteSetting::query()->firstOrFail();

        $this->assertNotNull($setting->logoUrl());
        $this->assertNotNull($setting->logoMonoUrl());
        $this->assertNotNull($setting->appleTouchIconUrl());
        $this->assertNotNull($setting->loginBackgroundUrl());
        $this->assertNotNull($setting->footerBackgroundUrl());
        $this->assertNotNull($setting->mainSearchBackgroundUrl());
        Storage::disk('public')->assertExists($setting->logo_path);
        Storage::disk('public')->assertExists($setting->logo_mono_path);
        Storage::disk('public')->assertExists($setting->apple_touch_icon_path);
        Storage::disk('public')->assertExists($setting->login_background_path);
        Storage::disk('public')->assertExists($setting->footer_background_path);
        Storage::disk('public')->assertExists($setting->main_search_background_path);
    }

    /**
     * Pedido del usuario: poder cambiar la imagen de fondo del buscador
     * principal (el hero de "Descubre lo mejor de tu municipio...") desde
     * Configuración del sitio. `main_search_background_path` ya existía en
     * el formulario, pero nada la usaba todavía — `search-hero.blade.php`
     * seguía sirviendo un asset estático hardcodeado como único fallback.
     */
    public function test_the_search_hero_falls_back_to_the_static_asset_without_a_configured_background(): void
    {
        $this->get(route('buscar'))
            ->assertOk()
            ->assertSee((string) Js::from(asset('images/backgrounds/fondo-buscador-principal.webp')), false);
    }

    public function test_the_search_hero_uses_the_configured_background_when_set(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(SiteSettings::class)
            ->fillForm(['main_search_background_path' => UploadedFile::fake()->image('search-bg.jpg')])
            ->call('save')
            ->assertHasNoFormErrors();

        $url = SiteSetting::current()->mainSearchBackgroundUrl();

        $this->get(route('buscar'))
            ->assertOk()
            ->assertSee((string) Js::from($url), false)
            ->assertDontSee((string) Js::from(asset('images/backgrounds/fondo-buscador-principal.webp')), false);
    }

    public function test_a_regular_user_cannot_access_the_site_settings_page(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        Livewire::test(SiteSettings::class)->assertForbidden();
    }

    /**
     * Pedido del usuario (2026-10-14): el título y la descripción del
     * buscador principal (hasta ahora solo la imagen era editable) y los
     * mismos tres campos (imagen, título, descripción) para los banners
     * de Eventos, Merkapuntos, Merkamigo Premia, Pídelo y la bienvenida
     * de "Crear vitrina" — todos hardcodeados en cada vista hasta hoy.
     * Un test por banner: cae al copy original sin nada configurado, y
     * usa lo configurado cuando sí existe.
     */
    public function test_the_search_hero_falls_back_to_the_default_title_and_description(): void
    {
        $this->get(route('buscar'))
            ->assertOk()
            ->assertSee('Descubre lo mejor de tu municipio')
            ->assertSee('Miles de negocios, productos y servicios cerca de ti.');
    }

    public function test_the_search_hero_uses_the_configured_title_and_description_when_set(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(SiteSettings::class)
            ->fillForm([
                'vitrinas_hero_title' => 'Título personalizado de vitrinas',
                'vitrinas_hero_description' => 'Descripción personalizada de vitrinas.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get(route('buscar'))
            ->assertOk()
            ->assertSee('Título personalizado de vitrinas')
            ->assertSee('Descripción personalizada de vitrinas.')
            ->assertDontSee('Miles de negocios, productos y servicios cerca de ti.');
    }

    public function test_the_eventos_banner_falls_back_to_the_default_image_title_and_description(): void
    {
        $this->get(route('eventos'))
            ->assertOk()
            ->assertSee(asset('images/backgrounds/fondo_banner_eventos.webp'), false)
            ->assertSee('Eventos')
            ->assertSee('cerca de ti')
            ->assertSee('Descubre experiencias de negocios locales: música en vivo, talleres, mercados y más.');
    }

    public function test_the_eventos_banner_uses_the_configured_image_title_and_description_when_set(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(SiteSettings::class)
            ->fillForm([
                'eventos_banner_path' => UploadedFile::fake()->image('eventos-bg.jpg'),
                'eventos_banner_title' => 'Título personalizado de eventos',
                'eventos_banner_description' => 'Descripción personalizada de eventos.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $url = SiteSetting::current()->eventosBannerUrl();

        $this->get(route('eventos'))
            ->assertOk()
            ->assertSee($url, false)
            ->assertSee('Título personalizado de eventos')
            ->assertSee('Descripción personalizada de eventos.')
            ->assertDontSee('Descubre experiencias de negocios locales: música en vivo, talleres, mercados y más.');
    }

    public function test_the_merkapuntos_banner_falls_back_to_the_default_image_title_and_description(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer);

        $this->get(route('merkapuntos'))
            ->assertOk()
            ->assertSee(asset('images/backgrounds/fondo_merkapuntos_banner.webp'), false)
            ->assertSee('Tu próxima compra puede acercarte a una recompensa.')
            ->assertSee('Elige lo que te gustaría disfrutar y empieza a acumular comprando cerca de ti.');
    }

    public function test_the_merkapuntos_banner_uses_the_configured_image_title_and_description_when_set(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(SiteSettings::class)
            ->fillForm([
                'merkapuntos_banner_path' => UploadedFile::fake()->image('merkapuntos-bg.jpg'),
                'merkapuntos_banner_title' => 'Título personalizado de Merkapuntos',
                'merkapuntos_banner_description' => 'Descripción personalizada de Merkapuntos.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $url = SiteSetting::current()->merkapuntosBannerUrl();

        $customer = User::factory()->create();
        $this->actingAs($customer);

        $this->get(route('merkapuntos'))
            ->assertOk()
            ->assertSee($url, false)
            ->assertSee('Título personalizado de Merkapuntos')
            ->assertSee('Descripción personalizada de Merkapuntos.')
            ->assertDontSee('Tu próxima compra puede acercarte a una recompensa.');
    }

    public function test_the_premia_banner_falls_back_to_the_default_image_title_and_description(): void
    {
        $this->get(route('premia.index'))
            ->assertOk()
            ->assertSee(asset('images/backgrounds/fondo_merkapuntos_premia.webp'), false)
            ->assertSee('Apoyar lo local tiene recompensa');
    }

    public function test_the_premia_banner_uses_the_configured_image_title_and_description_when_set(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(SiteSettings::class)
            ->fillForm([
                'premia_banner_path' => UploadedFile::fake()->image('premia-bg.jpg'),
                'premia_banner_title' => 'Título personalizado de Premia',
                'premia_banner_description' => 'Descripción personalizada de Premia.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $url = SiteSetting::current()->premiaBannerUrl();

        $this->get(route('premia.index'))
            ->assertOk()
            ->assertSee($url, false)
            ->assertSee('Título personalizado de Premia')
            ->assertSee('Descripción personalizada de Premia.')
            ->assertDontSee('Apoyar lo local tiene recompensa');
    }

    public function test_the_pidelo_banner_falls_back_to_the_default_image_title_and_description(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $this->get(route('pidelo.nueva'))
            ->assertOk()
            ->assertSee(asset('images/backgrounds/fondo_banner_pidelo.webp'), false)
            ->assertSee('Cuéntanos')
            ->assertSee('qué necesitas')
            ->assertSee('Compártelo una vez y recibe propuestas de negocios cercanos por acá mismo.');
    }

    public function test_the_pidelo_banner_uses_the_configured_image_title_and_description_when_set(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(SiteSettings::class)
            ->fillForm([
                'pidelo_banner_path' => UploadedFile::fake()->image('pidelo-bg.jpg'),
                'pidelo_banner_title' => 'Título personalizado de Pídelo',
                'pidelo_banner_description' => 'Descripción personalizada de Pídelo.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $url = SiteSetting::current()->pideloBannerUrl();

        $owner = User::factory()->create();
        $this->actingAs($owner);

        $this->get(route('pidelo.nueva'))
            ->assertOk()
            ->assertSee($url, false)
            ->assertSee('Título personalizado de Pídelo')
            ->assertSee('Descripción personalizada de Pídelo.')
            ->assertDontSee('Compártelo una vez y recibe propuestas de negocios cercanos por acá mismo.');
    }

    public function test_the_bienvenida_banner_falls_back_to_the_default_image_title_and_description(): void
    {
        $this->get(route('emprendedores.bienvenida'))
            ->assertOk()
            ->assertSee(asset('images/emprendedores/bienvenida-hero.jpg'), false)
            ->assertSee('Tu negocio local,')
            ->assertSee('más cerca de todos')
            ->assertSee('Crea tu vitrina digital en minutos, muestra lo que vendes y conecta con personas de tu comunidad.');
    }

    public function test_the_bienvenida_banner_uses_the_configured_image_title_and_description_when_set(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(SiteSettings::class)
            ->fillForm([
                'bienvenida_banner_path' => UploadedFile::fake()->image('bienvenida-bg.jpg'),
                'bienvenida_banner_title' => 'Título personalizado de bienvenida',
                'bienvenida_banner_description' => 'Descripción personalizada de bienvenida.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $url = SiteSetting::current()->bienvenidaBannerUrl();

        $this->get(route('emprendedores.bienvenida'))
            ->assertOk()
            ->assertSee($url, false)
            ->assertSee('Título personalizado de bienvenida')
            ->assertSee('Descripción personalizada de bienvenida.')
            ->assertDontSee('Crea tu vitrina digital en minutos, muestra lo que vendes y conecta con personas de tu comunidad.');
    }
}
