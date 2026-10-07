<?php

namespace Tests\Feature\Loyalty;

use App\Domain\Loyalty\Actions\CancelLoyaltyRedemption;
use App\Domain\Loyalty\Actions\CreateLoyaltyReward;
use App\Domain\Loyalty\Actions\DeliverLoyaltyRedemption;
use App\Domain\Loyalty\Actions\RegisterLoyaltyPurchase;
use App\Domain\Loyalty\Actions\ReserveLoyaltyRedemption;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyMovement;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use App\Domain\Loyalty\Models\LoyaltyReward;
use App\Domain\Loyalty\Notifications\RedemptionDelivered;
use App\Domain\Loyalty\Notifications\RedemptionPointsReleased;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\Feature\Loyalty\Concerns\SetsUpLoyaltyBusiness;
use Tests\TestCase;

/**
 * TODO_Merkapuntos.md, F1.5/F1.6/F1.8 — reserva, entrega, cancelación y
 * vencimiento de canjes.
 */
class LoyaltyRedemptionTest extends TestCase
{
    use RefreshDatabase, SetsUpLoyaltyBusiness;

    private function customerWithPoints(): array
    {
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 1_000);
        $customer = User::factory()->create();

        // 100.000 centavos de unidad ⇒ 100 puntos.
        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 100_000, Str::uuid()->toString());

        return [$business, $owner, $customer];
    }

    private function publishedReward(mixed $business, User $owner, array $overrides = []): LoyaltyReward
    {
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, array_merge([
            'title' => 'Café gratis',
            'points_cost' => 50,
            'full_cost_cents' => 300_000,
            'stock_total' => 1,
        ], $overrides));

        return app(CreateLoyaltyReward::class)->publish($reward, $owner);
    }

    public function test_reserving_a_redemption_debits_available_points_immediately(): void
    {
        [$business, $owner, $customer] = $this->customerWithPoints();
        $reward = $this->publishedReward($business, $owner);

        $result = app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, Str::uuid()->toString());

        $this->assertNotNull($result['token']);
        $this->assertStringStartsWith('rdm_', $result['token']);
        $this->assertSame(LoyaltyRedemption::RESERVADO, $result['redemption']->status);

        $account = $customer->loyaltyAccounts()->where('business_id', $business->id)->first();
        $this->assertSame(50, $account->availablePoints()); // 100 - 50 reservados
    }

    public function test_cannot_reserve_without_enough_points(): void
    {
        [$business, $owner, $customer] = $this->customerWithPoints();
        $reward = $this->publishedReward($business, $owner, ['points_cost' => 500]);

        $this->expectException(LoyaltyActionException::class);

        app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, Str::uuid()->toString());
    }

    public function test_the_last_unit_of_stock_cannot_be_reserved_twice(): void
    {
        // No reproduce una concurrencia real (dos conexiones en
        // paralelo); prueba que la guarda `lockForUpdate()` + recálculo
        // de stock dentro de la transacción rechaza correctamente un
        // segundo intento una vez la única unidad ya quedó reservada.
        [$business, $owner, $customerA] = $this->customerWithPoints();
        $customerB = User::factory()->create();
        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customerB, 100_000, Str::uuid()->toString());

        $reward = $this->publishedReward($business, $owner, ['stock_total' => 1, 'points_cost' => 10]);

        app(ReserveLoyaltyRedemption::class)->handle($customerA, $reward, Str::uuid()->toString());

        $this->expectException(LoyaltyActionException::class);

        app(ReserveLoyaltyRedemption::class)->handle($customerB, $reward, Str::uuid()->toString());
    }

    public function test_budget_exhaustion_blocks_further_reservations(): void
    {
        [$business, $owner, $customerA] = $this->customerWithPoints();
        $customerB = User::factory()->create();
        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customerB, 100_000, Str::uuid()->toString());

        $reward = $this->publishedReward($business, $owner, [
            'stock_total' => null,
            'points_cost' => 10,
            'full_cost_cents' => 300_000,
            'max_budget_cents' => 300_000, // alcanza para un solo canje
        ]);

        app(ReserveLoyaltyRedemption::class)->handle($customerA, $reward, Str::uuid()->toString());

        $this->expectException(LoyaltyActionException::class);

        app(ReserveLoyaltyRedemption::class)->handle($customerB, $reward, Str::uuid()->toString());
    }

    public function test_reserving_twice_with_the_same_idempotency_key_returns_the_same_redemption(): void
    {
        [$business, $owner, $customer] = $this->customerWithPoints();
        $reward = $this->publishedReward($business, $owner);
        $key = Str::uuid()->toString();

        $first = app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, $key);
        $second = app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, $key);

        $this->assertSame($first['redemption']->id, $second['redemption']->id);
        $this->assertSame($first['token'], $second['token']);
        $this->assertSame(1, LoyaltyRedemption::count());
    }

    public function test_an_employee_can_deliver_a_reserved_redemption_by_scanning_its_token(): void
    {
        Notification::fake();
        [$business, $owner, $customer] = $this->customerWithPoints();
        $reward = $this->publishedReward($business, $owner);

        $result = app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, Str::uuid()->toString());

        $deliverer = app(DeliverLoyaltyRedemption::class);
        $found = $deliverer->findByToken($business, $result['token']);
        $delivered = $deliverer->handle($business, $owner, $found);

        $this->assertSame(LoyaltyRedemption::ENTREGADO, $delivered->status);
        $this->assertSame($owner->id, $delivered->delivered_by_user_id);

        $reward->refresh();
        $this->assertSame(0, $reward->stock_reserved);
        $this->assertSame(1, $reward->stock_delivered);
        $this->assertSame(300_000, $reward->budget_spent_cents);

        Notification::assertSentTo($customer, RedemptionDelivered::class);
    }

    public function test_delivering_an_already_delivered_redemption_is_a_safe_no_op(): void
    {
        [$business, $owner, $customer] = $this->customerWithPoints();
        $reward = $this->publishedReward($business, $owner);
        $result = app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, Str::uuid()->toString());

        $deliverer = app(DeliverLoyaltyRedemption::class);
        $redemption = $deliverer->findByToken($business, $result['token']);
        $deliverer->handle($business, $owner, $redemption);
        $deliverer->handle($business, $owner, $redemption->fresh()); // reintento tras "fallo de red"

        $reward->refresh();
        $this->assertSame(1, $reward->stock_delivered); // no se descontó dos veces
    }

    public function test_cannot_deliver_against_a_token_from_another_business(): void
    {
        [$business, $owner, $customer] = $this->customerWithPoints();
        [$otherBusiness] = $this->enrolledBusiness();
        $reward = $this->publishedReward($business, $owner);
        $result = app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, Str::uuid()->toString());

        $this->expectException(LoyaltyActionException::class);

        app(DeliverLoyaltyRedemption::class)->findByToken($otherBusiness, $result['token']);
    }

    public function test_cancelling_a_reservation_releases_points_and_stock(): void
    {
        Notification::fake();
        [$business, $owner, $customer] = $this->customerWithPoints();
        $reward = $this->publishedReward($business, $owner);
        $result = app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, Str::uuid()->toString());

        app(CancelLoyaltyRedemption::class)->handle(
            $result['redemption'], CancelLoyaltyRedemption::RAZON_CLIENTE, $customer,
        );

        $account = $customer->loyaltyAccounts()->where('business_id', $business->id)->first();
        $this->assertSame(100, $account->availablePoints());

        $reward->refresh();
        $this->assertSame(0, $reward->stock_reserved);
        $this->assertSame(0, $reward->budget_reserved_cents);

        Notification::assertSentTo($customer, RedemptionPointsReleased::class);
    }

    public function test_delivery_wins_the_race_against_a_cancellation_attempt(): void
    {
        [$business, $owner, $customer] = $this->customerWithPoints();
        $reward = $this->publishedReward($business, $owner);
        $result = app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, Str::uuid()->toString());

        $deliverer = app(DeliverLoyaltyRedemption::class);
        $redemption = $deliverer->findByToken($business, $result['token']);
        $deliverer->handle($business, $owner, $redemption);

        $this->expectException(LoyaltyActionException::class);

        app(CancelLoyaltyRedemption::class)->handle($redemption->fresh(), CancelLoyaltyRedemption::RAZON_CLIENTE, $customer);
    }

    public function test_cancelling_an_already_cancelled_redemption_does_not_release_points_twice(): void
    {
        [$business, $owner, $customer] = $this->customerWithPoints();
        $reward = $this->publishedReward($business, $owner);
        $result = app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, Str::uuid()->toString());

        $canceller = app(CancelLoyaltyRedemption::class);
        $canceller->handle($result['redemption'], CancelLoyaltyRedemption::RAZON_CLIENTE, $customer);
        $canceller->handle($result['redemption']->fresh(), CancelLoyaltyRedemption::RAZON_CLIENTE, $customer);

        $this->assertSame(1, LoyaltyMovement::where('type', LoyaltyMovement::LIBERACION_CANJE)->count());
    }

    public function test_the_expiry_command_releases_reservations_past_their_deadline(): void
    {
        [$business, $owner, $customer] = $this->customerWithPoints();
        $reward = $this->publishedReward($business, $owner);
        $result = app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, Str::uuid()->toString());

        $result['redemption']->update(['expires_at' => now()->subMinute()]);

        $this->artisan('loyalty:expire-redemptions')->assertSuccessful();

        $this->assertSame(LoyaltyRedemption::EXPIRADO, $result['redemption']->fresh()->status);

        $account = $customer->loyaltyAccounts()->where('business_id', $business->id)->first();
        $this->assertSame(100, $account->availablePoints());
    }

    public function test_the_expiry_command_does_not_touch_reservations_still_within_their_window(): void
    {
        [$business, $owner, $customer] = $this->customerWithPoints();
        $reward = $this->publishedReward($business, $owner);
        $result = app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, Str::uuid()->toString());

        $this->artisan('loyalty:expire-redemptions')->assertSuccessful();

        $this->assertSame(LoyaltyRedemption::RESERVADO, $result['redemption']->fresh()->status);
    }
}
