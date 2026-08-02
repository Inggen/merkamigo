<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Domain\Trust\Models\Recommendation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 3.3 del TODO: recomendaciones enviadas por el cliente, reporte y
 * anti-abuso.
 */
class RecommendationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function completedOrder(): array
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Recomendable'])->business;
        $customer = User::factory()->create();

        $order = OrderConfirmation::create([
            'business_id' => $business->id,
            'created_by' => $owner->id,
            'customer_user_id' => $customer->id,
            'business_user_id' => $owner->id,
            'customer_confirmed_at' => now(),
            'business_confirmed_at' => now(),
            'completed_at' => now(),
            'status' => OrderConfirmation::COMPLETADO,
            'is_reputation_eligible' => true,
            'source_type' => $business->getMorphClass(),
            'source_id' => $business->id,
            'summary' => 'Pedido completado',
        ]);

        return [$order, $business, $customer];
    }

    public function test_a_customer_can_recommend_a_business_after_a_completed_order(): void
    {
        [$order, , $customer] = $this->completedOrder();

        $this->actingAs($customer);

        Livewire::test('pages::clientes.pedidos')
            ->call('recommend', $order->id)
            ->set('recommendation_body', 'Llegó puntual y todo fresco.')
            ->set('recommendation_tags', ['Cumplió a tiempo'])
            ->call('submitRecommendation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('recommendations', [
            'order_confirmation_id' => $order->id,
            'author_user_id' => $customer->id,
            'status' => Recommendation::PENDIENTE,
        ]);
    }

    public function test_a_customer_cannot_recommend_the_same_order_twice(): void
    {
        [$order, , $customer] = $this->completedOrder();

        Recommendation::create([
            'business_id' => $order->business_id,
            'order_confirmation_id' => $order->id,
            'author_user_id' => $customer->id,
            'status' => Recommendation::PENDIENTE,
            'body' => 'Primera recomendación.',
        ]);

        $this->actingAs($customer);

        Livewire::test('pages::clientes.pedidos')
            ->call('recommend', $order->id)
            ->set('recommendation_body', 'Segundo intento.')
            ->call('submitRecommendation')
            ->assertHasErrors(['recommendation_body']);

        $this->assertSame(1, Recommendation::where('order_confirmation_id', $order->id)->count());
    }

    public function test_a_customer_cannot_recommend_an_order_that_is_not_completed(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Pendiente'])->business;
        $customer = User::factory()->create();

        $order = OrderConfirmation::create([
            'business_id' => $business->id,
            'created_by' => $owner->id,
            'customer_user_id' => $customer->id,
            'source_type' => $business->getMorphClass(),
            'source_id' => $business->id,
            'summary' => 'Pedido sin completar',
        ]);

        $this->actingAs($customer);

        Livewire::test('pages::clientes.pedidos')
            ->call('recommend', $order->id)
            ->set('recommendation_body', 'Intento anticipado.')
            ->call('submitRecommendation')
            ->assertHasErrors(['recommendation_body']);

        $this->assertSame(0, Recommendation::count());
    }

    public function test_a_recommendation_with_a_link_is_rejected(): void
    {
        [$order, , $customer] = $this->completedOrder();

        $this->actingAs($customer);

        Livewire::test('pages::clientes.pedidos')
            ->call('recommend', $order->id)
            ->set('recommendation_body', 'Escríbeme a https://spam.example.com')
            ->call('submitRecommendation')
            ->assertHasErrors(['recommendation_body']);

        $this->assertSame(0, Recommendation::count());
    }

    public function test_a_guest_can_report_a_published_recommendation(): void
    {
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Reportable', 'whatsapp_number' => '+573001112233', 'category_id' => $category->id,
            'municipality_id' => $municipality->id, 'description' => 'Descripción.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Producto', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        app(PublishStorefront::class)->handle($business->fresh(), $owner);

        $recommendation = Recommendation::create([
            'business_id' => $business->id,
            'status' => Recommendation::PUBLICADA,
            'body' => 'Excelente atención.',
            'published_at' => now(),
        ]);

        $response = $this->post(route('reportes.guardar.recomendacion', [$business->fresh(), $recommendation]), [
            'reason' => 'spam',
        ]);

        $response->assertRedirect(route('vitrinas.show', $business->fresh()));

        $this->assertDatabaseHas('reports', [
            'reportable_type' => $recommendation->getMorphClass(),
            'reportable_id' => $recommendation->id,
            'reason' => 'spam',
        ]);
    }
}
