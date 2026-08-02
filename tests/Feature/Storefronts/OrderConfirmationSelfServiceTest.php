<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Actions\ConfirmOrder;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Domain\Trust\Notifications\OrderPendingYourConfirmation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 2.3/3.2 del TODO: autoservicio de pedidos confirmados (completar,
 * reportar un problema, cancelar) y constancia "desde un contacto".
 */
class OrderConfirmationSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_business_can_register_a_direct_order_for_a_real_customer_account(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Directo'])->business;
        $customer = User::factory()->create(['email' => 'cliente@example.com']);

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.oportunidades', ['business' => $business->id])
            ->set('direct_order_customer_email', 'cliente@example.com')
            ->set('direct_order_summary', '2 tortas de chocolate')
            ->call('registerDirectOrder')
            ->assertHasNoErrors();

        $order = OrderConfirmation::where('business_id', $business->id)->firstOrFail();
        $this->assertSame($customer->id, $order->customer_user_id);
        $this->assertNotNull($order->business_confirmed_at);
        $this->assertNull($order->customer_confirmed_at);

        Notification::assertSentTo($customer, OrderPendingYourConfirmation::class);
    }

    public function test_registering_a_direct_order_for_an_unknown_email_is_rejected(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Directo'])->business;

        $this->actingAs($owner);

        Livewire::test('pages::emprendedores.negocios.oportunidades', ['business' => $business->id])
            ->set('direct_order_customer_email', 'nadie@example.com')
            ->set('direct_order_summary', 'Algo')
            ->call('registerDirectOrder')
            ->assertHasErrors(['direct_order_customer_email']);

        $this->assertSame(0, OrderConfirmation::count());
    }

    public function test_customer_can_confirm_complete_and_the_business_can_see_it_confirmed(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Pedido'])->business;
        $customer = User::factory()->create();

        $order = OrderConfirmation::create([
            'business_id' => $business->id,
            'created_by' => $owner->id,
            'customer_user_id' => $customer->id,
            'business_user_id' => $owner->id,
            'business_confirmed_at' => now(),
            'source_type' => $business->getMorphClass(),
            'source_id' => $business->id,
            'summary' => 'Pedido de prueba',
        ]);

        $this->actingAs($customer);

        Livewire::test('pages::clientes.pedidos')
            ->call('confirm', $order->id)
            ->assertHasNoErrors();

        $this->assertSame(OrderConfirmation::CONFIRMADO, $order->fresh()->status);

        Livewire::test('pages::clientes.pedidos')
            ->call('complete', $order->id);

        $this->assertSame(OrderConfirmation::COMPLETADO, $order->fresh()->status);
        $this->assertTrue($order->fresh()->is_reputation_eligible);
    }

    public function test_a_customer_can_cancel_a_pending_order(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Pedido'])->business;
        $customer = User::factory()->create();

        $order = OrderConfirmation::create([
            'business_id' => $business->id,
            'created_by' => $owner->id,
            'customer_user_id' => $customer->id,
            'source_type' => $business->getMorphClass(),
            'source_id' => $business->id,
            'summary' => 'Pedido a cancelar',
        ]);

        $this->actingAs($customer);

        Livewire::test('pages::clientes.pedidos')->call('cancel', $order->id);

        $this->assertSame(OrderConfirmation::CANCELADO, $order->fresh()->status);
    }

    public function test_a_customer_cannot_act_on_someone_elses_order(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Pedido'])->business;
        $customer = User::factory()->create();
        $stranger = User::factory()->create();

        $order = OrderConfirmation::create([
            'business_id' => $business->id,
            'created_by' => $owner->id,
            'customer_user_id' => $customer->id,
            'source_type' => $business->getMorphClass(),
            'source_id' => $business->id,
            'summary' => 'Pedido ajeno',
        ]);

        $this->actingAs($stranger);

        Livewire::test('pages::clientes.pedidos')
            ->call('confirm', $order->id)
            ->assertForbidden();

        $this->assertNull($order->fresh()->customer_confirmed_at);
    }

    public function test_cancel_is_a_noop_once_the_order_is_completed(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Pedido'])->business;
        $customer = User::factory()->create();

        $order = OrderConfirmation::create([
            'business_id' => $business->id,
            'created_by' => $owner->id,
            'customer_user_id' => $customer->id,
            'business_user_id' => $owner->id,
            'customer_confirmed_at' => now(),
            'business_confirmed_at' => now(),
            'status' => OrderConfirmation::CONFIRMADO,
            'source_type' => $business->getMorphClass(),
            'source_id' => $business->id,
            'summary' => 'Pedido completado',
        ]);

        app(ConfirmOrder::class)->complete($order, $owner);
        $this->assertSame(OrderConfirmation::COMPLETADO, $order->fresh()->status);

        app(ConfirmOrder::class)->cancel($order->fresh(), $owner);

        $this->assertSame(OrderConfirmation::COMPLETADO, $order->fresh()->status);
    }
}
