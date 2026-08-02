<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\Payment;
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
