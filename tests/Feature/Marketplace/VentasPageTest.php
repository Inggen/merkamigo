<?php

namespace Tests\Feature\Marketplace;

use App\Domain\Marketplace\Actions\ApplyApprovedOrder;
use App\Domain\Marketplace\Actions\ConnectBusinessWompi;
use App\Domain\Marketplace\Actions\CreateOrderCheckout;
use App\Domain\Marketplace\Models\CommissionCharge;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class VentasPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_charge_the_accrued_commission_from_the_panel(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Ventas'])->business;
        $business->update(['wompi_payment_source_id' => '123', 'auto_renew_enabled' => true]);

        $product = $business->products()->create([
            'name' => 'Producto', 'slug' => 'producto-ventas', 'type' => 'producto',
            'price' => 40000, 'price_type' => 'exacto', 'status' => 'publicado', 'is_available' => true,
        ]);

        BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica',
            'expires_at' => now()->addYear(),
        ]);

        Http::fake(['*/merchants/*' => Http::response(['data' => ['id' => 1]], 200)]);
        app(ConnectBusinessWompi::class)->handle($business, [
            'public_key' => 'pub_test', 'private_key' => 'prv_test',
            'integrity_secret' => 'i', 'events_secret' => 'e', 'environment' => 'sandbox',
        ], $owner);

        $buyer = User::factory()->create();
        $order = app(CreateOrderCheckout::class)->handle($product, 1, $buyer);
        app(ApplyApprovedOrder::class)->handle($order, 'APPROVED', 'txn', []);

        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'com-1', 'status' => 'APPROVED']], 200)]);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.ventas', ['business' => $business->id])
            ->assertSee(__('Comisión pendiente de cobro'))
            ->call('chargeNow');

        $this->assertSame(CommissionCharge::PAGADA, CommissionCharge::first()->status);
    }

    public function test_a_collaborator_of_another_business_cannot_open_ventas(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Ajeno'])->business;

        $outsider = User::factory()->create();
        $this->actingAs($outsider);

        Livewire::test('pages::emprendedores.negocios.ventas', ['business' => $business->id])
            ->assertForbidden();
    }
}
