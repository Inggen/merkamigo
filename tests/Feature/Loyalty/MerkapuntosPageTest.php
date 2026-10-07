<?php

namespace Tests\Feature\Loyalty;

use App\Domain\Loyalty\Actions\CreateLoyaltyReward;
use App\Domain\Loyalty\Actions\RegisterLoyaltyPurchase;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Feature\Loyalty\Concerns\SetsUpLoyaltyBusiness;
use Tests\TestCase;

/**
 * TODO_Merkapuntos.md, F2.4/F2.5 — la sección "Merkapuntos" del cliente.
 */
class MerkapuntosPageTest extends TestCase
{
    use RefreshDatabase, SetsUpLoyaltyBusiness;

    public function test_a_customer_without_any_points_sees_the_empty_state(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('merkapuntos'))
            ->assertOk()
            ->assertSee('Todavía no tienes Merkapuntos');
    }

    public function test_a_customer_sees_their_balance_and_redeemable_rewards(): void
    {
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 1_000);
        $customer = User::factory()->create();
        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 100_000, Str::uuid()->toString());

        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café gratis',
            'points_cost' => 50,
            'full_cost_cents' => 300_000,
        ]);
        app(CreateLoyaltyReward::class)->publish($reward, $owner);

        $this->actingAs($customer)->get(route('merkapuntos'))
            ->assertOk()
            ->assertSee($business->name)
            ->assertSee('100')
            ->assertSee('Café gratis')
            ->assertSee('Usar 50 Merkapuntos');
    }

    public function test_the_home_feed_shows_rewards_as_an_automatic_right_sidebar_carousel(): void
    {
        [$business, $owner] = $this->enrolledBusiness();

        foreach (['Café latte', 'Torta especial', 'Helado artesanal'] as $title) {
            $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
                'title' => $title,
                'points_cost' => 400,
                'full_cost_cents' => 800_000,
            ]);
            app(CreateLoyaltyReward::class)->publish($reward, $owner);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder([
                'feed-right-sidebar',
                __('También te puede interesar'),
                __('Negocios cerca de ti'),
            ], false)
            ->assertSee('window.setInterval', false)
            ->assertSee('grid-cols-2', false)
            ->assertSee('group-hover:opacity-100', false)
            ->assertSee('Café latte')
            ->assertSee('Torta especial')
            ->assertSee('Helado artesanal');
    }

    public function test_a_customer_can_reserve_a_redemption_from_the_page(): void
    {
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 1_000);
        $customer = User::factory()->create();
        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 100_000, Str::uuid()->toString());

        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café gratis',
            'points_cost' => 50,
            'full_cost_cents' => 300_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);

        Livewire::actingAs($customer)
            ->test('pages::merkapuntos.index')
            ->call('reserve', $reward->id)
            ->assertSet('reservedToken', fn ($token) => str_starts_with($token, 'rdm_'));

        $this->assertSame(1, LoyaltyRedemption::count());
    }

    public function test_a_customer_can_cancel_their_own_active_redemption(): void
    {
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 1_000);
        $customer = User::factory()->create();
        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 100_000, Str::uuid()->toString());

        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café gratis',
            'points_cost' => 50,
            'full_cost_cents' => 300_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);

        $component = Livewire::actingAs($customer)
            ->test('pages::merkapuntos.index')
            ->call('reserve', $reward->id);

        $redemption = LoyaltyRedemption::firstOrFail();

        $component->call('cancelRedemption', $redemption->id);

        $this->assertSame(LoyaltyRedemption::CANCELADO, $redemption->fresh()->status);
    }

    public function test_a_customer_cannot_cancel_someone_elses_redemption(): void
    {
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 1_000);
        $customer = User::factory()->create();
        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 100_000, Str::uuid()->toString());

        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café gratis',
            'points_cost' => 50,
            'full_cost_cents' => 300_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);

        Livewire::actingAs($customer)->test('pages::merkapuntos.index')->call('reserve', $reward->id);
        $redemption = LoyaltyRedemption::firstOrFail();

        $stranger = User::factory()->create();

        Livewire::actingAs($stranger)
            ->test('pages::merkapuntos.index')
            ->call('cancelRedemption', $redemption->id)
            ->assertForbidden();
    }

    public function test_the_identity_qr_endpoint_returns_a_png_for_the_authenticated_customer(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->get(route('merkapuntos.qr'));

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }

    public function test_the_redemption_qr_endpoint_rejects_another_customers_redemption(): void
    {
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 1_000);
        $customer = User::factory()->create();
        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 100_000, Str::uuid()->toString());

        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café gratis',
            'points_cost' => 50,
            'full_cost_cents' => 300_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);

        Livewire::actingAs($customer)->test('pages::merkapuntos.index')->call('reserve', $reward->id);
        $redemption = LoyaltyRedemption::firstOrFail();

        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('merkapuntos.qr.redemption', $redemption))
            ->assertForbidden();
    }
}
