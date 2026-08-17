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
}
