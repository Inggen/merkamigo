<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Domain\Trust\Models\Recommendation;
use App\Livewire\SubmitOpinionForm;
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
            ->set('recommendation_rating', 5)
            ->set('recommendation_tags', ['Cumplió a tiempo'])
            ->call('submitRecommendation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('recommendations', [
            'order_confirmation_id' => $order->id,
            'author_user_id' => $customer->id,
            'status' => Recommendation::PENDIENTE,
            'rating' => 5,
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
            ->set('recommendation_rating', 4)
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
            ->set('recommendation_rating', 5)
            ->call('submitRecommendation')
            ->assertHasErrors(['recommendation_body']);

        $this->assertSame(0, Recommendation::count());
    }

    public function test_a_customer_cannot_recommend_without_choosing_a_rating(): void
    {
        [$order, , $customer] = $this->completedOrder();

        $this->actingAs($customer);

        Livewire::test('pages::clientes.pedidos')
            ->call('recommend', $order->id)
            ->set('recommendation_body', 'Sin calificación.')
            ->call('submitRecommendation')
            ->assertHasErrors(['recommendation_rating']);

        $this->assertSame(0, Recommendation::count());
    }

    public function test_a_recommendation_with_a_link_is_rejected(): void
    {
        [$order, , $customer] = $this->completedOrder();

        $this->actingAs($customer);

        Livewire::test('pages::clientes.pedidos')
            ->call('recommend', $order->id)
            ->set('recommendation_body', 'Escríbeme a https://spam.example.com')
            ->set('recommendation_rating', 3)
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

    /**
     * @return array{0: Business, 1: User}
     */
    private function publishedBusiness(): array
    {
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos-'.uniqid(), 'is_active' => true]);
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica-'.uniqid(), 'department' => 'Cundinamarca', 'is_active' => true]);
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Opinable', 'whatsapp_number' => '+573001112233', 'category_id' => $category->id,
            'municipality_id' => $municipality->id, 'description' => 'Descripción.',
        ])->business;
        $business->update(['logo_path' => 'businesses/'.$business->id.'/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Producto', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        app(PublishStorefront::class)->handle($business->fresh(), $owner);

        return [$business->fresh(), $owner];
    }

    /**
     * Pedido del usuario: una "opinión" sobre el negocio (a diferencia de
     * la recomendación ligada a un pedido, ver los tests de arriba) la
     * puede dejar cualquier usuario registrado sin haber comprado nada —
     * el único requisito es estar autenticado.
     */
    public function test_a_registered_user_can_submit_an_opinion_without_any_purchase(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Opinable'])->business;
        $author = User::factory()->create();

        $this->actingAs($author);

        Livewire::test(SubmitOpinionForm::class, ['business' => $business])
            ->set('body', 'Excelente atención, muy recomendado.')
            ->set('rating', 5)
            ->set('tags', ['Buena atención'])
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('recommendations', [
            'business_id' => $business->id,
            'order_confirmation_id' => null,
            'author_user_id' => $author->id,
            'status' => Recommendation::PENDIENTE,
            'rating' => 5,
        ]);
    }

    public function test_a_registered_user_cannot_submit_an_opinion_without_choosing_a_rating(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Opinable'])->business;
        $author = User::factory()->create();

        $this->actingAs($author);

        Livewire::test(SubmitOpinionForm::class, ['business' => $business])
            ->set('body', 'Sin calificación.')
            ->call('submit')
            ->assertHasErrors(['rating']);

        $this->assertSame(0, Recommendation::count());
    }

    public function test_a_user_cannot_submit_two_opinions_for_the_same_business(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Opinable'])->business;
        $author = User::factory()->create();

        Recommendation::create([
            'business_id' => $business->id,
            'author_user_id' => $author->id,
            'status' => Recommendation::PENDIENTE,
            'body' => 'Primera opinión.',
        ]);

        $this->actingAs($author);

        Livewire::test(SubmitOpinionForm::class, ['business' => $business])
            ->set('body', 'Segundo intento.')
            ->set('rating', 4)
            ->call('submit')
            ->assertHasErrors(['body']);

        $this->assertSame(1, Recommendation::where('business_id', $business->id)->whereNull('order_confirmation_id')->count());
    }

    public function test_an_opinion_with_a_link_is_rejected(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Opinable'])->business;
        $author = User::factory()->create();

        $this->actingAs($author);

        Livewire::test(SubmitOpinionForm::class, ['business' => $business])
            ->set('body', 'Escríbeme a https://spam.example.com')
            ->set('rating', 5)
            ->call('submit')
            ->assertHasErrors(['body']);

        $this->assertSame(0, Recommendation::count());
    }

    public function test_a_guest_is_redirected_to_login_when_submitting_an_opinion(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Opinable'])->business;

        Livewire::test(SubmitOpinionForm::class, ['business' => $business])
            ->set('body', 'Intento sin sesión.')
            ->call('submit')
            ->assertRedirect(route('login'));

        $this->assertSame(0, Recommendation::count());
    }

    public function test_the_storefront_shows_the_opinion_form_for_an_authenticated_user_and_a_login_prompt_for_guests(): void
    {
        [$business] = $this->publishedBusiness();
        $customer = User::factory()->create();

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee(__('Inicia sesión para dejar tu opinión sobre este negocio.'));

        $this->actingAs($customer);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee(__('Deja tu opinión'));
    }

    /**
     * Pedido del usuario: las 5 estrellas fijas de "Opiniones de clientes"
     * no salían de ningún dato real — ahora deben reflejar el promedio de
     * las calificaciones publicadas, y no contar las recomendaciones de
     * antes de que existiera `rating` como si tuvieran 0 estrellas.
     */
    public function test_the_storefront_shows_the_real_average_rating(): void
    {
        [$business] = $this->publishedBusiness();

        Recommendation::create(['business_id' => $business->id, 'status' => Recommendation::PUBLICADA, 'body' => 'A', 'rating' => 5, 'published_at' => now()]);
        Recommendation::create(['business_id' => $business->id, 'status' => Recommendation::PUBLICADA, 'body' => 'B', 'rating' => 3, 'published_at' => now()]);
        // Sin calificación (anterior a la migración) — no debe contar como 0.
        Recommendation::create(['business_id' => $business->id, 'status' => Recommendation::PUBLICADA, 'body' => 'C', 'published_at' => now()]);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee('4.0')
            ->assertDontSee(__('Todavía sin calificaciones.'));
    }

    public function test_the_storefront_shows_no_ratings_yet_without_any_rated_recommendation(): void
    {
        [$business] = $this->publishedBusiness();

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee(__('Todavía sin calificaciones.'));
    }

    /**
     * Con calificaciones reales ya existiendo, el schema.org de la vitrina
     * (usado por buscadores) debe reportarlas — un `Review` sin
     * `reviewRating` y un negocio sin `aggregateRating` son tan
     * incompletos como las estrellas fijas que se quitaron de la vista.
     */
    public function test_the_storefront_schema_includes_the_aggregate_rating(): void
    {
        [$business] = $this->publishedBusiness();

        Recommendation::create(['business_id' => $business->id, 'status' => Recommendation::PUBLICADA, 'body' => 'A', 'rating' => 5, 'published_at' => now()]);
        Recommendation::create(['business_id' => $business->id, 'status' => Recommendation::PUBLICADA, 'body' => 'B', 'rating' => 3, 'published_at' => now()]);

        $this->get(route('vitrinas.show', $business))
            ->assertOk()
            ->assertSee('"@type":"AggregateRating"', false)
            ->assertSee('"ratingValue":4', false)
            ->assertSee('"reviewCount":2', false)
            ->assertSee('"@type":"Rating"', false);
    }
}
