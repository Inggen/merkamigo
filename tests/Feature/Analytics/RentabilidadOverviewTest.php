<?php

namespace Tests\Feature\Analytics;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Marketplace\Actions\ApplyApprovedOrder;
use App\Domain\Marketplace\Actions\ConnectBusinessWompi;
use App\Domain\Marketplace\Actions\CreateOrderCheckout;
use App\Domain\Marketplace\Models\CommissionCharge;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Models\BusinessVerification;
use App\Filament\Widgets\RentabilidadOverview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * PR5 de TODO_VENTAS_RENTABILIDAD.md (P1.4, hallazgo #2 de la
 * auditoría): panel gerencial de GMV/comisión/MRR en /admin.
 */
class RentabilidadOverviewTest extends TestCase
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

    public function test_a_regular_business_owner_cannot_view_the_widget(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $this->assertFalse(RentabilidadOverview::canView());
    }

    public function test_a_moderator_cannot_view_the_widget(): void
    {
        $moderator = User::factory()->create();
        $this->assignPlatformRole($moderator, 'moderator');
        $this->actingAs($moderator);

        $this->assertFalse(RentabilidadOverview::canView());
    }

    public function test_an_admin_can_view_the_widget(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $this->assertTrue(RentabilidadOverview::canView());
    }

    public function test_the_widget_computes_gmv_and_commission_states_correctly(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Rentabilidad'])->business;

        BusinessVerification::create([
            'business_id' => $business->id, 'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica', 'expires_at' => now()->addYear(),
        ]);
        Http::fake(['*/merchants/*' => Http::response(['data' => ['id' => 1]], 200)]);
        app(ConnectBusinessWompi::class)->handle($business, [
            'public_key' => 'pub_test_rent', 'private_key' => 'prv_test_rent',
            'integrity_secret' => 'integrity-rent', 'events_secret' => 'events-rent',
            'environment' => 'sandbox',
        ], $owner);

        $product = $business->products()->create([
            'name' => 'Producto', 'slug' => 'producto-rent', 'type' => 'producto',
            'price' => 100000, 'price_type' => 'exacto', 'status' => 'publicado', 'is_available' => true,
        ]);
        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);
        app(ApplyApprovedOrder::class)->handle($order, 'APPROVED', 'txn-1', []);

        $charge = CommissionCharge::first();
        $charge->update(['status' => CommissionCharge::PAGADA]);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $component = Livewire::test(RentabilidadOverview::class)->assertSuccessful();
        $stats = (fn () => $this->getStats())->call($component->instance());

        $this->assertSame('$100.000 COP', $stats[0]->getValue());
        $this->assertSame('$5.000 COP', $stats[1]->getValue());
        $this->assertSame('$0 COP', $stats[2]->getValue());
        $this->assertSame(0, $stats[3]->getValue());
        $this->assertSame(1, AnalyticsEvent::where('type', AnalyticsEvent::MARKETPLACE_CHECKOUT_STARTED)->count());
    }

    public function test_the_widget_separates_plan_mrr_from_addon_mrr_and_ignores_trials_and_free_plans(): void
    {
        $freePlan = Plan::firstOrCreate(['slug' => 'gratis'], [
            'name' => 'Básico', 'price_cents' => null,
            'billing_period' => Plan::MENSUAL, 'is_active' => true, 'position' => 0,
        ]);
        $paidPlan = Plan::create([
            'slug' => 'emprendedor', 'name' => 'Emprendedor', 'price_cents' => 4990000,
            'billing_period' => Plan::MENSUAL, 'is_active' => true, 'position' => 1,
        ]);

        // Negocio en plan de pago activo — cuenta para MRR de planes.
        $paidOwner = User::factory()->create();
        $paidBusiness = app(CreateStorefront::class)->handle($paidOwner, ['name' => 'Negocio Pago'])->business;
        $paidBusiness->update(['status' => 'publicado']);
        Subscription::create(['business_id' => $paidBusiness->id, 'plan_id' => $paidPlan->id, 'status' => Subscription::ACTIVA]);

        // Negocio en PRUEBA del mismo plan — no debe contar (todavía no pagó).
        $trialOwner = User::factory()->create();
        $trialBusiness = app(CreateStorefront::class)->handle($trialOwner, ['name' => 'Negocio Prueba'])->business;
        $trialBusiness->update(['status' => 'publicado']);
        Subscription::create(['business_id' => $trialBusiness->id, 'plan_id' => $paidPlan->id, 'status' => Subscription::PRUEBA, 'trial_ends_at' => now()->addDays(10)]);

        // Negocio en plan gratis — no debe contar.
        $freeOwner = User::factory()->create();
        $freeBusiness = app(CreateStorefront::class)->handle($freeOwner, ['name' => 'Negocio Gratis'])->business;
        $freeBusiness->update(['status' => 'publicado']);
        Subscription::create(['business_id' => $freeBusiness->id, 'plan_id' => $freePlan->id, 'status' => Subscription::ACTIVA]);

        // Add-on recurrente activo — cuenta para MRR de add-ons, no de planes.
        $addonProduct = BillingProduct::create([
            'slug' => 'asistente-ia', 'name' => 'Asistente IA', 'price_cents' => 4990000,
            'kind' => BillingProduct::ENTITLEMENT,
            'payload' => ['entitlement_key' => BusinessEntitlement::AI_CHATBOT, 'expires_in_days' => 30],
            'is_active' => true,
        ]);
        BusinessEntitlement::create([
            'business_id' => $freeBusiness->id, 'key' => BusinessEntitlement::AI_CHATBOT,
            'source_billing_product_id' => $addonProduct->id, 'expires_at' => now()->addDays(20),
        ]);

        // Add-on de por vida (expires_at null) — no debe contar como MRR.
        BusinessEntitlement::create([
            'business_id' => $trialBusiness->id, 'key' => BusinessEntitlement::AI_CHATBOT,
            'source_billing_product_id' => $addonProduct->id, 'expires_at' => null,
        ]);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        $component = Livewire::test(RentabilidadOverview::class)->assertSuccessful();
        $stats = (fn () => $this->getStats())->call($component->instance());

        $this->assertSame('$49.900 COP', $stats[4]->getValue());
        $this->assertSame('$49.900 COP', $stats[5]->getValue());
    }
}
