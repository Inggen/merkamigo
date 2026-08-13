<?php

namespace Tests\Feature\Platform;

use App\Domain\Platform\Models\SiteSetting;
use App\Filament\Pages\SiteSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_a_regular_user_cannot_access_the_site_settings_page(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        Livewire::test(SiteSettings::class)->assertForbidden();
    }
}
