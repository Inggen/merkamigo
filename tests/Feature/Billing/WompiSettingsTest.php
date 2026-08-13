<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Models\WompiSetting;
use App\Filament\Pages\WompiSettings;
use App\Models\User;
use App\Support\Wompi\WompiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 4.2 del TODO: configuración de Wompi (sandbox y producción) editable
 * desde el admin, sin tocar `.env` ni desplegar de nuevo.
 */
class WompiSettingsTest extends TestCase
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

    public function test_without_any_saved_settings_the_client_falls_back_to_env_config(): void
    {
        $client = app(WompiClient::class);

        $this->assertSame(config('services.wompi.public_key'), $client->publicKey());
        $this->assertSame(config('services.wompi.checkout_url'), $client->checkoutUrl());
    }

    public function test_an_admin_can_save_sandbox_and_production_credentials(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(WompiSettings::class)
            ->fillForm([
                'active_env' => 'sandbox',
                'sandbox_public_key' => 'pub_test_abc',
                'sandbox_private_key' => 'prv_test_abc',
                'sandbox_integrity_secret' => 'integrity_test_abc',
                'sandbox_events_secret' => 'events_test_abc',
                'production_public_key' => 'pub_prod_xyz',
                'production_private_key' => 'prv_prod_xyz',
                'production_integrity_secret' => 'integrity_prod_xyz',
                'production_events_secret' => 'events_prod_xyz',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('wompi_settings', [
            'active_env' => 'sandbox',
            'sandbox_public_key' => 'pub_test_abc',
            'production_public_key' => 'pub_prod_xyz',
        ]);
    }

    public function test_saved_secrets_are_never_prefilled_back_into_the_form(): void
    {
        WompiSetting::create([
            'active_env' => 'sandbox',
            'sandbox_public_key' => 'pub_test_abc',
            'sandbox_private_key' => 'prv_test_abc',
            'sandbox_integrity_secret' => 'integrity_test_abc',
            'sandbox_events_secret' => 'events_test_abc',
        ]);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(WompiSettings::class)
            ->assertSet('data.sandbox_public_key', 'pub_test_abc')
            ->assertSet('data.sandbox_private_key', null)
            ->assertSet('data.sandbox_integrity_secret', null)
            ->assertSet('data.sandbox_events_secret', null);
    }

    public function test_saving_again_with_blank_secret_fields_does_not_erase_the_saved_secrets(): void
    {
        WompiSetting::create([
            'active_env' => 'sandbox',
            'sandbox_public_key' => 'pub_test_abc',
            'sandbox_private_key' => 'prv_test_abc',
            'sandbox_integrity_secret' => 'integrity_test_abc',
            'sandbox_events_secret' => 'events_test_abc',
        ]);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        // Simula recargar la página (los secretos llegan en null, como en
        // `mount()`) y guardar sin haber vuelto a escribirlos.
        Livewire::test(WompiSettings::class)
            ->fillForm(['sandbox_public_key' => 'pub_test_abc_actualizada'])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = WompiSetting::query()->firstOrFail();
        $this->assertSame('pub_test_abc_actualizada', $setting->sandbox_public_key);
        $this->assertSame('prv_test_abc', $setting->getRawOriginal('sandbox_private_key'));
        $this->assertSame('integrity_test_abc', $setting->getRawOriginal('sandbox_integrity_secret'));
        $this->assertSame('events_test_abc', $setting->getRawOriginal('sandbox_events_secret'));
    }

    public function test_a_regular_business_owner_cannot_access_the_settings_page(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        Livewire::test(WompiSettings::class)->assertForbidden();
    }

    public function test_the_client_uses_sandbox_credentials_when_sandbox_is_active(): void
    {
        WompiSetting::create([
            'active_env' => 'sandbox',
            'sandbox_public_key' => 'pub_test_sandbox_key',
            'sandbox_integrity_secret' => 'sandbox_secret',
            'production_public_key' => 'pub_prod_should_not_be_used',
        ]);

        $client = app(WompiClient::class);

        $this->assertSame('pub_test_sandbox_key', $client->publicKey());
        $this->assertStringContainsString('sandbox.wompi.co', $client->voidTransactionUrl('txn-1'));

        $expectedSignature = hash('sha256', 'REF-1'.'5000'.'COP'.'sandbox_secret');
        $this->assertSame($expectedSignature, $client->integritySignature('REF-1', 5000));
    }

    public function test_the_client_switches_to_production_credentials_when_production_is_active(): void
    {
        WompiSetting::create([
            'active_env' => 'production',
            'sandbox_public_key' => 'pub_test_should_not_be_used',
            'production_public_key' => 'pub_prod_live_key',
            'production_integrity_secret' => 'prod_secret',
        ]);

        $client = app(WompiClient::class);

        $this->assertSame('pub_prod_live_key', $client->publicKey());
        $this->assertStringContainsString('production.wompi.co', $client->voidTransactionUrl('txn-1'));

        $expectedSignature = hash('sha256', 'REF-1'.'5000'.'COP'.'prod_secret');
        $this->assertSame($expectedSignature, $client->integritySignature('REF-1', 5000));
    }

    public function test_a_blank_saved_field_still_falls_back_to_env_config(): void
    {
        WompiSetting::create([
            'active_env' => 'sandbox',
            'sandbox_public_key' => null,
        ]);

        $client = app(WompiClient::class);

        $this->assertSame(config('services.wompi.public_key'), $client->publicKey());
    }
}
