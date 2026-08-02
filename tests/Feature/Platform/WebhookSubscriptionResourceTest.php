<?php

namespace Tests\Feature\Platform;

use App\Domain\Platform\Models\WebhookSubscription;
use App\Filament\Resources\WebhookSubscriptions\Pages\CreateWebhookSubscription;
use App\Filament\Resources\WebhookSubscriptions\Pages\ListWebhookSubscriptions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 5.4 del TODO: gestión de suscripciones de webhook, exclusiva de
 * admin/superadmin.
 */
class WebhookSubscriptionResourceTest extends TestCase
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

    public function test_an_admin_can_create_a_webhook_subscription(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(CreateWebhookSubscription::class)
            ->fillForm([
                'url' => 'https://aliado.test/webhook',
                'secret' => 'un-secreto-largo',
                'subscribed_events' => ['business.published'],
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('webhook_subscriptions', ['url' => 'https://aliado.test/webhook']);
    }

    public function test_a_regular_business_owner_cannot_access_the_resource(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        Livewire::test(ListWebhookSubscriptions::class)->assertForbidden();
    }

    public function test_the_list_shows_existing_subscriptions(): void
    {
        WebhookSubscription::create([
            'business_id' => null,
            'url' => 'https://aliado.test/webhook',
            'secret' => 'x',
            'subscribed_events' => ['business.published'],
            'is_active' => true,
        ]);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(ListWebhookSubscriptions::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(WebhookSubscription::all());
    }
}
