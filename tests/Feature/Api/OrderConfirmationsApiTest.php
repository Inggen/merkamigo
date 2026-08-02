<?php

namespace Tests\Feature\Api;

use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Actions\RequestDirectOrderConfirmation;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 5.1/3.2 del TODO: pedidos confirmados vía API, del lado del comprador o
 * del negocio.
 */
class OrderConfirmationsApiTest extends TestCase
{
    use RefreshDatabase;

    private function directOrder(User $customer): array
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Directo', 'whatsapp_number' => '+573001112233'])->business;

        $order = app(RequestDirectOrderConfirmation::class)->handle($business, $owner, $customer->email, 'Compró una torta.');

        return [$owner, $business, $order->fresh()];
    }

    public function test_the_customer_can_confirm_their_side_of_the_order(): void
    {
        $customer = User::factory()->create();
        [, , $order] = $this->directOrder($customer);
        Sanctum::actingAs($customer);

        $this->postJson(route('api.v1.order-confirmations.confirmar', $order))
            ->assertOk()
            ->assertJsonPath('data.status', OrderConfirmation::CONFIRMADO);
    }

    public function test_the_business_can_complete_a_confirmed_order_and_the_customer_can_then_recommend_it(): void
    {
        $customer = User::factory()->create();
        [$owner, , $order] = $this->directOrder($customer);

        Sanctum::actingAs($customer);
        $this->postJson(route('api.v1.order-confirmations.confirmar', $order))->assertOk();

        Sanctum::actingAs($owner);
        $this->postJson(route('api.v1.order-confirmations.completar', $order))
            ->assertOk()
            ->assertJsonPath('data.status', OrderConfirmation::COMPLETADO);

        Sanctum::actingAs($customer);
        $this->postJson(route('api.v1.order-confirmations.recomendacion', $order), [
            'body' => 'Excelente atención, todo a tiempo.',
            'tags' => ['Cumplió a tiempo'],
        ])->assertCreated()->assertJsonPath('data.body', 'Excelente atención, todo a tiempo.');
    }

    public function test_a_stranger_cannot_act_on_someone_elses_order(): void
    {
        $customer = User::factory()->create();
        [, , $order] = $this->directOrder($customer);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.order-confirmations.confirmar', $order))->assertForbidden();
        $this->postJson(route('api.v1.order-confirmations.cancelar', $order))->assertForbidden();
    }

    public function test_the_index_lists_orders_for_both_the_customer_and_the_business(): void
    {
        $customer = User::factory()->create();
        [$owner, , $order] = $this->directOrder($customer);

        Sanctum::actingAs($customer);
        $this->getJson(route('api.v1.order-confirmations.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->id);

        Sanctum::actingAs($owner);
        $this->getJson(route('api.v1.order-confirmations.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->id);
    }
}
