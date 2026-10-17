<?php

namespace Tests\Feature\Marketplace;

use App\Domain\Marketplace\Actions\ConnectBusinessWompi;
use App\Domain\Marketplace\Models\CommissionCharge;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Models\BusinessVerification;
use App\Filament\Resources\CommissionCharges\Pages\ListCommissionCharges;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Panel de conciliación del Marketplace en /admin (PR2 de
 * TODO_VENTAS_RENTABILIDAD.md, hallazgo #2 de la auditoría,
 * docs/auditoria-ventas-rentabilidad.md §1.4): antes ningún superadmin
 * podía ver desde /admin el GMV ni el estado de las comisiones.
 */
class CommissionConciliationPanelTest extends TestCase
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

    private function openCharge(string $businessName): CommissionCharge
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => $businessName])->business;
        $business->update(['wompi_payment_source_id' => '123', 'auto_renew_enabled' => true]);

        BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica',
            'expires_at' => now()->addYear(),
        ]);

        Http::fake(['*/merchants/*' => Http::response(['data' => ['id' => 1]], 200)]);
        app(ConnectBusinessWompi::class)->handle($business, [
            'public_key' => 'pub_test_'.$business->id, 'private_key' => 'prv_test_'.$business->id,
            'integrity_secret' => 'integrity-'.$business->id, 'events_secret' => 'events-'.$business->id,
            'environment' => 'sandbox',
        ], $owner);

        return CommissionCharge::create([
            'business_id' => $business->id,
            'period_start' => now()->subDays(10)->toDateString(),
            'period_end' => now()->toDateString(),
            'orders_count' => 1,
            'gross_amount_cents' => 5000000,
            'commission_cents' => 250000,
            'status' => CommissionCharge::ABIERTA,
        ]);
    }

    public function test_an_admin_can_see_the_commission_conciliation_list(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $charge = $this->openCharge('Negocio Panel');

        Livewire::test(ListCommissionCharges::class)
            ->assertOk()
            ->assertSee($charge->business->name)
            ->assertSee('$50.000')
            ->assertSee('$2.500');
    }

    public function test_a_regular_business_owner_cannot_open_the_commission_panel(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        Livewire::test(ListCommissionCharges::class)->assertForbidden();
    }

    public function test_a_regular_business_owner_cannot_open_the_orders_panel(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        Livewire::test(ListOrders::class)->assertForbidden();
    }

    public function test_only_a_superadmin_sees_the_charge_now_action_and_it_actually_charges(): void
    {
        $superadmin = User::factory()->create();
        $this->assignPlatformRole($superadmin, 'superadmin');
        $this->actingAs($superadmin);

        $charge = $this->openCharge('Negocio Cobrable');

        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'com-1', 'status' => 'APPROVED']], 200)]);

        Livewire::test(ListCommissionCharges::class)
            ->assertTableActionExists('chargeNow')
            ->callTableAction('chargeNow', $charge);

        $this->assertSame(CommissionCharge::PAGADA, $charge->fresh()->status);
    }

    public function test_an_admin_without_superadmin_does_not_see_the_charge_now_action(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $charge = $this->openCharge('Negocio Solo Lectura');

        Livewire::test(ListCommissionCharges::class)
            ->assertTableActionHidden('chargeNow', $charge);
    }
}
