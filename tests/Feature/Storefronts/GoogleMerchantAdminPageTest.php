<?php

namespace Tests\Feature\Storefronts;

use App\Filament\Pages\GoogleMerchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoogleMerchantAdminPageTest extends TestCase
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

    public function test_an_admin_can_open_the_google_merchant_page(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');

        $this->actingAs($admin)
            ->get('/admin/google-merchant')
            ->assertOk()
            ->assertSee('Google Merchant');
    }

    public function test_a_moderator_cannot_open_the_google_merchant_page(): void
    {
        $moderator = User::factory()->create();
        $this->assignPlatformRole($moderator, 'moderator');

        $this->actingAs($moderator);

        $this->assertFalse(GoogleMerchant::canAccess());
    }
}
