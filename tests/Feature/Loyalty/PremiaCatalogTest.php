<?php

namespace Tests\Feature\Loyalty;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Loyalty\Actions\CreateLoyaltyReward;
use App\Domain\Loyalty\Actions\RegisterLoyaltyPurchase;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Loyalty\Concerns\SetsUpLoyaltyBusiness;
use Tests\TestCase;

/**
 * TODO_Merkapuntos.md, F2.2/F2.3/F2.5 — catálogo público de recompensas y
 * el canje contextual (login/registro vuelve exactamente al "Canjear"
 * que el invitado pulsó).
 */
class PremiaCatalogTest extends TestCase
{
    use RefreshDatabase, SetsUpLoyaltyBusiness;

    public function test_the_public_catalog_lists_published_rewards_without_login(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café de la casa', 'points_cost' => 400, 'full_cost_cents' => 800_000,
        ]);
        app(CreateLoyaltyReward::class)->publish($reward, $owner);

        // Un borrador nunca debe aparecer en el catálogo público.
        app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Premio sin publicar', 'points_cost' => 100, 'full_cost_cents' => 100_000,
        ]);

        $response = $this->get(route('premia.index'));

        $response->assertOk();
        $response->assertSee('Café de la casa');
        $response->assertDontSee('Premio sin publicar');
        $response->assertSee('images/backgrounds/fondo_merkapuntos_premia.webp');
    }

    public function test_the_catalog_only_shows_rewards_from_businesses_with_an_active_enrollment(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café de la casa', 'points_cost' => 400, 'full_cost_cents' => 800_000,
        ]);
        app(CreateLoyaltyReward::class)->publish($reward, $owner);

        // Retirar el negocio del programa no debe seguir anunciando sus premios.
        $business->loyaltyEnrollment->update(['status' => 'retirada']);

        $this->get(route('premia.index'))->assertDontSee('Café de la casa');
    }

    public function test_the_catalog_filters_by_municipality(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café de la casa', 'points_cost' => 400, 'full_cost_cents' => 800_000,
        ]);
        app(CreateLoyaltyReward::class)->publish($reward, $owner);

        Municipality::create([
            'name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true,
        ]);

        $this->get(route('premia.index', ['municipio' => 'cajica']))->assertSee('Café de la casa');
        // Un municipio real que ese negocio no sirve sí debe excluirlo
        // (a diferencia de un slug inválido, que no filtra nada — mismo
        // criterio permisivo que `PlazaController::buscar`).
        $this->get(route('premia.index', ['municipio' => 'zipaquira']))->assertDontSee('Café de la casa');
    }

    public function test_the_reward_detail_page_shows_business_and_accumulation_rule(): void
    {
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 100_000);
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café de la casa', 'points_cost' => 400, 'full_cost_cents' => 800_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);

        $response = $this->get(route('premia.show', $reward));

        $this->assertSame('cafe-de-la-casa', $reward->slug);
        $this->assertStringEndsWith('/premia/cafe-de-la-casa', route('premia.show', $reward));
        $response->assertOk();
        $response->assertSee('Café de la casa');
        $response->assertSee($business->name);
        $response->assertSee('400 Merkapuntos');

        $this->get('/premia/'.$reward->id)
            ->assertStatus(301)
            ->assertRedirect(route('premia.show', $reward));
    }

    public function test_only_the_business_owner_sees_the_reward_edit_shortcut(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café de la casa', 'points_cost' => 400, 'full_cost_cents' => 800_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);
        $editUrl = route('emprendedores.negocios.merkapuntos', $business).'#premio-'.$reward->id;

        $this->actingAs($owner)
            ->get(route('premia.show', $reward))
            ->assertOk()
            ->assertSee('Editar premio')
            ->assertSee($editUrl, false);

        $this->actingAs(User::factory()->create())
            ->get(route('premia.show', $reward))
            ->assertOk()
            ->assertDontSee('Editar premio');
    }

    public function test_the_reward_detail_shows_the_promotion_and_all_product_images(): void
    {
        Storage::fake('public');
        [$business, $owner] = $this->enrolledBusiness();
        $product = $business->products()->create([
            'name' => 'Postre artesanal',
            'slug' => 'postre-artesanal',
            'price_type' => 'sin_precio',
        ]);
        $product->media()->createMany([
            ['path' => 'products/postre-uno.webp', 'type' => 'image', 'position' => 0],
            ['path' => 'products/postre-dos.webp', 'type' => 'image', 'position' => 1],
        ]);

        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'product_id' => $product->id,
            'title' => 'Promoción de postre',
            'points_cost' => 300,
            'full_cost_cents' => 900_000,
        ]);
        $reward->update(['image_path' => 'rewards/promocion.webp']);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);

        $this->get(route('premia.show', $reward))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url('rewards/promocion.webp'))
            ->assertSee(Storage::disk('public')->url('products/postre-uno.webp'))
            ->assertSee(Storage::disk('public')->url('products/postre-dos.webp'))
            ->assertSee('Galería del premio');
    }

    public function test_a_draft_reward_is_not_publicly_reachable(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Premio sin publicar', 'points_cost' => 100, 'full_cost_cents' => 100_000,
        ]);

        $this->get(route('premia.show', $reward))->assertNotFound();
    }

    public function test_a_guest_who_clicks_redeem_is_sent_to_login_and_returns_to_redeem_it_after_authenticating(): void
    {
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 1_000);
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café de la casa', 'points_cost' => 10, 'full_cost_cents' => 800_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);
        $customer = User::factory()->create();
        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 100_000, Str::uuid()->toString());

        // El invitado pulsa "Canjear" → `redeem` exige auth → login
        // conserva la URL como `url.intended`.
        $redeemUrl = route('premia.redeem', $reward);
        $this->get($redeemUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', $redeemUrl);

        // Autenticado, vuelve exactamente a esa acción.
        $this->actingAs($customer)->get($redeemUrl)->assertRedirect(route('merkapuntos'));

        $this->assertSame(1, LoyaltyRedemption::count());
        $this->assertSame($customer->id, LoyaltyRedemption::first()->account->user_id);
    }

    public function test_an_authenticated_customer_sees_their_own_points_balance_at_that_business(): void
    {
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 1_000);
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café de la casa', 'points_cost' => 400, 'full_cost_cents' => 800_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);
        $customer = User::factory()->create();
        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 1_067_000, Str::uuid()->toString());

        $this->actingAs($customer)
            ->get(route('premia.show', $reward))
            ->assertOk()
            ->assertSee('Tus Merkapuntos')
            ->assertSee('1.067')
            // Pedido del usuario (2026-10-06): un enlace directo a ver
            // todos sus Merkapuntos, no solo el saldo en este negocio.
            ->assertSee(route('merkapuntos'), false);
    }

    public function test_a_guest_does_not_see_a_points_balance_card(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café de la casa', 'points_cost' => 400, 'full_cost_cents' => 800_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);

        $this->get(route('premia.show', $reward))
            ->assertOk()
            ->assertDontSee('Tus Merkapuntos');
    }

    public function test_the_detail_page_suggests_other_published_rewards(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café de la casa', 'points_cost' => 400, 'full_cost_cents' => 800_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);

        $other = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Torta especial', 'points_cost' => 600, 'full_cost_cents' => 1_200_000,
        ]);
        app(CreateLoyaltyReward::class)->publish($other, $owner);

        $draft = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Premio sin publicar', 'points_cost' => 100, 'full_cost_cents' => 100_000,
        ]);

        $this->get(route('premia.show', $reward))
            ->assertOk()
            ->assertSee('También te puede interesar')
            ->assertSee('Torta especial')
            ->assertDontSee('Premio sin publicar');
    }

    public function test_redeeming_without_enough_points_returns_to_the_reward_with_an_error(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café de la casa', 'points_cost' => 999999, 'full_cost_cents' => 800_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('premia.redeem', $reward))
            ->assertRedirect(route('premia.show', $reward));

        $this->assertSame(0, LoyaltyRedemption::count());
    }
}
