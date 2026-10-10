<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Moderation\Models\SupportTicket;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Database\Seeders\BillingProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 4.3 del TODO: catálogo de productos de ingreso complementario.
 */
class BillingProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function destacado(): BillingProduct
    {
        return BillingProduct::create([
            'slug' => 'destacado-7',
            'name' => 'Destacado 7 días',
            'description' => 'Aparece primero en la Plaza durante 7 días.',
            'price_cents' => 990000,
            'kind' => BillingProduct::DESTACADO,
            'payload' => ['days' => 7],
            'is_active' => true,
        ]);
    }

    private function vitrinaAsistida(): BillingProduct
    {
        return BillingProduct::create([
            'slug' => 'vitrina-asistida',
            'name' => 'Vitrina asistida',
            'description' => 'Te ayudamos a pulir tu vitrina.',
            'price_cents' => 4990000,
            'kind' => BillingProduct::VITRINA_ASISTIDA,
            'payload' => null,
            'is_active' => true,
        ]);
    }

    public function test_the_catalog_page_shows_active_products_and_hides_inactive_ones(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Catálogo'])->business;

        $active = $this->destacado();
        BillingProduct::create([
            'slug' => 'inactivo', 'name' => 'Producto inactivo', 'description' => 'x',
            'price_cents' => 100000, 'kind' => BillingProduct::DESTACADO, 'payload' => null, 'is_active' => false,
        ]);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.impulsar', ['business' => $business->id])
            ->assertSee('7 días')
            ->assertDontSee('Producto inactivo');
    }

    public function test_the_catalog_page_shows_the_featured_days_comparison_and_extra_products(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Impulso'])->business;

        $this->seed(BillingProductSeeder::class);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.impulsar', ['business' => $business->id])
            ->assertSee('7 días')
            ->assertSee('14 días')
            ->assertSee('30 días')
            ->assertSee('Más elegido')
            ->assertSee('Mejor precio')
            ->assertSee('1.414 por día')
            ->assertSee('Ahorras')
            ->assertSee('Vitrina asistida')
            ->assertSee('Kit Arranca Bonito')
            ->assertSee('Todo incluido')
            ->assertSee('Solicitar ayuda')
            ->assertSee('Comprar kit');
    }

    public function test_buying_a_featured_product_extends_featured_until_once_wompi_approves(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Destacado'])->business;
        $product = $this->destacado();

        $this->actingAs($owner)
            ->get(route('emprendedores.negocios.impulsar.checkout', ['business' => $business, 'billingProduct' => $product]))
            ->assertOk();

        $payment = Payment::where('business_id', $business->id)->firstOrFail();
        $this->assertSame($product->id, $payment->billing_product_id);

        Http::fake([
            '*/transactions/wompi-txn-featured' => Http::response([
                'data' => ['id' => 'wompi-txn-featured', 'status' => 'APPROVED', 'reference' => $payment->reference],
            ], 200),
        ]);

        $this->get(route('billing.checkout.return', ['id' => 'wompi-txn-featured']))->assertOk();

        $business->refresh();
        $this->assertTrue($business->isFeatured());
        $this->assertTrue($business->featured_until->isFuture());
    }

    public function test_buying_vitrina_asistida_creates_a_support_ticket_once_wompi_approves(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Vitrina Asistida'])->business;
        $product = $this->vitrinaAsistida();

        $this->actingAs($owner)
            ->get(route('emprendedores.negocios.impulsar.checkout', ['business' => $business, 'billingProduct' => $product]))
            ->assertOk();

        $payment = Payment::where('business_id', $business->id)->firstOrFail();

        Http::fake([
            '*/transactions/wompi-txn-vitrina' => Http::response([
                'data' => ['id' => 'wompi-txn-vitrina', 'status' => 'APPROVED', 'reference' => $payment->reference],
            ], 200),
        ]);

        $this->get(route('billing.checkout.return', ['id' => 'wompi-txn-vitrina']))->assertOk();

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $owner->id,
            'status' => SupportTicket::PENDIENTE,
        ]);
    }

    private function aiAssistant(): BillingProduct
    {
        return BillingProduct::create([
            'slug' => 'asistente-ia',
            'name' => 'Asistente IA para tu vitrina',
            'description' => 'Chat con IA.',
            'price_cents' => 4990000,
            'kind' => BillingProduct::ENTITLEMENT,
            'payload' => ['entitlement_key' => BusinessEntitlement::AI_CHATBOT, 'expires_in_days' => 30],
            'is_active' => true,
        ]);
    }

    /**
     * PR4 de TODO_VENTAS_RENTABILIDAD.md, decisión #5 del usuario
     * 2026-10-09: nunca ofrecer de nuevo una capacidad que ya se
     * tiene — ni por plan ni por una compra anterior.
     */
    public function test_the_catalog_hides_the_ai_assistant_addon_when_already_covered_by_the_top_plan(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Negocios Catálogo'])->business;
        $this->aiAssistant();

        $plan = Plan::create([
            'slug' => 'negocios', 'name' => 'Negocios', 'price_cents' => 9900000,
            'billing_period' => Plan::MENSUAL, 'is_active' => true, 'position' => 2,
        ]);
        Subscription::create(['business_id' => $business->id, 'plan_id' => $plan->id, 'status' => Subscription::ACTIVA]);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.impulsar', ['business' => $business->id])
            ->assertDontSee('Asistente IA para tu vitrina');
    }

    public function test_the_catalog_hides_the_ai_assistant_addon_when_already_purchased(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Ya Lo Tiene'])->business;
        $this->aiAssistant();
        BusinessEntitlement::create([
            'business_id' => $business->id,
            'key' => BusinessEntitlement::AI_CHATBOT,
            'expires_at' => now()->addDays(20),
        ]);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.impulsar', ['business' => $business->id])
            ->assertDontSee('Asistente IA para tu vitrina');
    }

    /**
     * Decisión #4: un add-on recurrente necesita tarjeta guardada para
     * poder renovarse — sin una, el botón manda a guardarla primero en
     * vez de al checkout.
     */
    public function test_the_catalog_sends_to_save_a_card_instead_of_checkout_when_the_addon_is_recurring_and_there_is_none(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Sin Tarjeta'])->business;
        $this->aiAssistant();

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.impulsar', ['business' => $business->id])
            ->assertSee('Guardar tarjeta para activarlo')
            ->assertSeeHtml(route('emprendedores.negocios.plan', $business))
            ->assertDontSeeHtml(route('emprendedores.negocios.impulsar.checkout', ['business' => $business, 'billingProduct' => BillingProduct::where('slug', 'asistente-ia')->first()]));
    }

    public function test_starting_checkout_for_an_entitlement_already_active_via_plan_is_rejected(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Rechazo Plan'])->business;
        $product = $this->aiAssistant();

        $plan = Plan::create([
            'slug' => 'negocios', 'name' => 'Negocios', 'price_cents' => 9900000,
            'billing_period' => Plan::MENSUAL, 'is_active' => true, 'position' => 2,
        ]);
        Subscription::create(['business_id' => $business->id, 'plan_id' => $plan->id, 'status' => Subscription::ACTIVA]);

        $this->actingAs($owner)
            ->get(route('emprendedores.negocios.impulsar.checkout', ['business' => $business, 'billingProduct' => $product]))
            ->assertRedirect()
            ->assertSessionHasErrors('coupon');
    }

    public function test_starting_checkout_for_a_recurring_addon_without_a_saved_card_is_rejected(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Rechazo Tarjeta'])->business;
        $product = $this->aiAssistant();

        $this->actingAs($owner)
            ->get(route('emprendedores.negocios.impulsar.checkout', ['business' => $business, 'billingProduct' => $product]))
            ->assertRedirect()
            ->assertSessionHasErrors('coupon');
    }

    public function test_someone_who_cannot_manage_the_business_cannot_start_a_checkout_for_a_billing_product(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Ajeno'])->business;
        $product = $this->destacado();

        $this->actingAs(User::factory()->create())
            ->get(route('emprendedores.negocios.impulsar.checkout', ['business' => $business, 'billingProduct' => $product]))
            ->assertForbidden();
    }
}
