<?php

namespace Tests\Feature\Loyalty;

use App\Domain\Loyalty\Actions\RegisterLoyaltyPurchase;
use App\Domain\Loyalty\Actions\ReverseLoyaltyPurchase;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyAccount;
use App\Domain\Loyalty\Models\LoyaltyMovement;
use App\Domain\Loyalty\Models\LoyaltyPurchase;
use App\Domain\Loyalty\Notifications\PointsAccrued;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\Feature\Loyalty\Concerns\SetsUpLoyaltyBusiness;
use Tests\TestCase;

/**
 * TODO_Merkapuntos.md, F1.3/F1.4/F1.7 — registro de compra presencial,
 * duplicados y devoluciones/ajustes.
 */
class LoyaltyPurchaseTest extends TestCase
{
    use RefreshDatabase, SetsUpLoyaltyBusiness;

    public function test_registering_a_purchase_awards_points_per_policy(): void
    {
        Notification::fake();
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 100_000);
        $customer = User::factory()->create();

        $purchase = app(RegisterLoyaltyPurchase::class)->handle(
            $business, $owner, $customer, eligibleAmountCents: 350_000, idempotencyKey: Str::uuid()->toString(),
        );

        $this->assertSame(3, $purchase->points_awarded);
        $this->assertSame(LoyaltyPurchase::REGISTRADA, $purchase->status);

        $account = LoyaltyAccount::where('business_id', $business->id)->where('user_id', $customer->id)->first();
        $this->assertNotNull($account);
        $this->assertSame(3, $account->availablePoints());

        Notification::assertSentTo($customer, PointsAccrued::class);
    }

    public function test_retrying_with_the_same_idempotency_key_does_not_accrue_twice(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();
        $key = Str::uuid()->toString();

        $first = app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 500_000, $key);
        $second = app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 500_000, $key);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, LoyaltyPurchase::count());
        $this->assertSame(1, LoyaltyMovement::where('type', LoyaltyMovement::ACUMULACION)->count());
    }

    public function test_a_repeated_external_reference_is_rejected_not_silently_duplicated(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();

        app(RegisterLoyaltyPurchase::class)->handle(
            $business, $owner, $customer, 500_000, Str::uuid()->toString(), externalReference: 'FAC-001',
        );

        $this->expectException(LoyaltyActionException::class);

        app(RegisterLoyaltyPurchase::class)->handle(
            $business, $owner, $customer, 500_000, Str::uuid()->toString(), externalReference: 'FAC-001',
        );
    }

    public function test_two_presencial_purchases_without_a_reference_both_succeed(): void
    {
        // F1.4: NULL no colisiona consigo mismo — las compras presenciales
        // sin recibo pueden coexistir.
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();

        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 500_000, Str::uuid()->toString());
        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 500_000, Str::uuid()->toString());

        $this->assertSame(2, LoyaltyPurchase::count());
    }

    public function test_a_possible_duplicate_purchase_is_flagged_but_not_blocked(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();

        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 500_000, Str::uuid()->toString());
        $second = app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 500_000, Str::uuid()->toString());

        $this->assertSame(2, LoyaltyPurchase::count());
        $movement = LoyaltyMovement::where('reference_id', $second->id)->first();
        $this->assertTrue((bool) ($movement->metadata['possible_duplicate'] ?? false));
    }

    public function test_an_employee_without_a_business_role_cannot_register_a_purchase(): void
    {
        [$business] = $this->enrolledBusiness();
        $stranger = User::factory()->create();
        $customer = User::factory()->create();

        $this->expectException(LoyaltyActionException::class);

        app(RegisterLoyaltyPurchase::class)->handle($business, $stranger, $customer, 500_000, Str::uuid()->toString());
    }

    public function test_an_employee_from_another_business_cannot_register_a_purchase(): void
    {
        [$business] = $this->enrolledBusiness();
        [, $otherOwner] = $this->enrolledBusiness();
        $customer = User::factory()->create();

        $this->expectException(LoyaltyActionException::class);

        app(RegisterLoyaltyPurchase::class)->handle($business, $otherOwner, $customer, 500_000, Str::uuid()->toString());
    }

    public function test_a_business_without_an_active_policy_cannot_register_purchases(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $business->loyaltyPolicies()->update(['status' => 'archivada']);
        $customer = User::factory()->create();

        $this->expectException(LoyaltyActionException::class);

        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 500_000, Str::uuid()->toString());
    }

    public function test_reversing_a_purchase_can_push_the_balance_negative_without_truncating_to_zero(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();

        $purchase = app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 300_000, Str::uuid()->toString());
        $this->assertSame(3, $purchase->points_awarded);

        // El cliente ya gastó puntos en otro lado antes de la devolución
        // (simulado con un ajuste manual) — revertir la compra completa
        // debe dejar el saldo en negativo, no en cero.
        $account = LoyaltyAccount::where('business_id', $business->id)->where('user_id', $customer->id)->first();
        LoyaltyMovement::create([
            'account_id' => $account->id,
            'type' => LoyaltyMovement::AJUSTE,
            'points' => -2,
            'idempotency_key' => Str::uuid()->toString(),
        ]);
        $this->assertSame(1, $account->availablePoints());

        app(ReverseLoyaltyPurchase::class)->handle($purchase, 3, Str::uuid()->toString(), $owner, 'Producto devuelto');

        $this->assertSame(-2, $account->availablePoints());
        $this->assertSame(LoyaltyPurchase::REVERTIDA, $purchase->fresh()->status);
    }

    public function test_reversal_is_idempotent_on_retry(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();
        $purchase = app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 300_000, Str::uuid()->toString());

        $key = Str::uuid()->toString();
        app(ReverseLoyaltyPurchase::class)->handle($purchase, 3, $key, $owner);
        app(ReverseLoyaltyPurchase::class)->handle($purchase, 3, $key, $owner);

        $this->assertSame(1, LoyaltyMovement::where('type', LoyaltyMovement::REVERSION)->count());
    }

    public function test_cannot_reverse_more_points_than_were_awarded(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();
        $purchase = app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 300_000, Str::uuid()->toString());

        $this->expectException(LoyaltyActionException::class);

        app(ReverseLoyaltyPurchase::class)->handle($purchase, 999, Str::uuid()->toString(), $owner);
    }

    public function test_a_purchase_can_be_reversed_partially_and_the_remainder_reversed_later(): void
    {
        // F5.2: "reversión parcial/total" — devolver solo parte del
        // producto comprado no debe revertir los puntos de lo que el
        // cliente sí se quedó.
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();
        $purchase = app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 1_000_000, Str::uuid()->toString());
        $this->assertSame(10, $purchase->points_awarded);

        app(ReverseLoyaltyPurchase::class)->handle($purchase, 4, Str::uuid()->toString(), $owner, 'Devolución parcial.');

        $this->assertSame(LoyaltyPurchase::REGISTRADA, $purchase->fresh()->status);

        $account = LoyaltyAccount::where('business_id', $business->id)->where('user_id', $customer->id)->first();
        $this->assertSame(6, $account->availablePoints());

        // Revertir el resto (6 de los 10 originales) sí debe agotar el
        // saldo reversible y marcar la compra como totalmente revertida.
        app(ReverseLoyaltyPurchase::class)->handle($purchase->fresh(), 6, Str::uuid()->toString(), $owner, 'Devolución del resto.');

        $this->assertSame(LoyaltyPurchase::REVERTIDA, $purchase->fresh()->status);
        $this->assertSame(0, $account->availablePoints());

        // Y ya no queda nada por revertir.
        $this->expectException(LoyaltyActionException::class);
        app(ReverseLoyaltyPurchase::class)->handle($purchase->fresh(), 1, Str::uuid()->toString(), $owner);
    }
}
