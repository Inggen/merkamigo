<?php

namespace Tests\Feature\Loyalty;

use App\Domain\Loyalty\Actions\CreateLoyaltyReward;
use App\Domain\Loyalty\Actions\ReviewLoyaltyReceiptClaim;
use App\Domain\Loyalty\Actions\SubmitLoyaltyReceiptClaim;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyPurchase;
use App\Domain\Loyalty\Models\LoyaltyReceiptClaim;
use App\Domain\Loyalty\Models\LoyaltyReward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Loyalty\Concerns\SetsUpLoyaltyBusiness;
use Tests\TestCase;

/**
 * TODO_Merkapuntos.md, F2.7/F3.5/F3.6 — lado del negocio: pausar/reactivar
 * premios y revisar solicitudes excepcionales con recibo.
 */
class LoyaltyBusinessActionsTest extends TestCase
{
    use RefreshDatabase, SetsUpLoyaltyBusiness;

    public function test_pausing_a_published_reward_makes_it_not_redeemable_and_resuming_restores_it(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café gratis', 'points_cost' => 50, 'full_cost_cents' => 300_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);
        $this->assertTrue($reward->isRedeemable());

        $reward = app(CreateLoyaltyReward::class)->pause($reward, $owner);
        $this->assertSame(LoyaltyReward::PAUSADO, $reward->status);
        $this->assertFalse($reward->isRedeemable());

        $reward = app(CreateLoyaltyReward::class)->resume($reward, $owner);
        $this->assertSame(LoyaltyReward::PUBLICADO, $reward->status);
        $this->assertTrue($reward->isRedeemable());
    }

    public function test_cannot_pause_a_reward_that_is_not_published(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café gratis', 'points_cost' => 50, 'full_cost_cents' => 300_000,
        ]);

        $this->expectException(LoyaltyActionException::class);

        app(CreateLoyaltyReward::class)->pause($reward, $owner);
    }

    public function test_approving_a_receipt_claim_creates_a_purchase_and_accrues_points(): void
    {
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 1_000);
        $customer = User::factory()->create();
        $claim = $this->submitClaim($business, $customer);

        $purchase = app(ReviewLoyaltyReceiptClaim::class)->approve($claim, $owner, 50_000, Str::uuid()->toString());

        $this->assertSame(50, $purchase->points_awarded);
        $this->assertSame(LoyaltyReceiptClaim::APROBADA, $claim->fresh()->status);
        $this->assertSame($purchase->id, $claim->fresh()->linked_purchase_id);
    }

    public function test_rejecting_a_receipt_claim_requires_a_reason_and_does_not_accrue(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();
        $claim = $this->submitClaim($business, $customer);

        app(ReviewLoyaltyReceiptClaim::class)->reject($claim, $owner, 'El recibo no corresponde a este negocio.');

        $this->assertSame(LoyaltyReceiptClaim::RECHAZADA, $claim->fresh()->status);
        $this->assertSame(0, LoyaltyPurchase::count());
    }

    public function test_submitting_the_same_receipt_twice_creates_two_independent_claims_for_human_review(): void
    {
        // F5.2: "solicitud de recibo duplicada" — F2.7 es explícito en que
        // "no hay acreditación automática por imagen": el sistema no
        // intenta adivinar que son el mismo recibo, deja ambas
        // solicitudes pendientes para que el negocio decida. Aprobar la
        // primera no afecta la segunda.
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 1_000);
        $customer = User::factory()->create();

        $first = $this->submitClaim($business, $customer);
        $second = $this->submitClaim($business, $customer);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, LoyaltyReceiptClaim::where('business_id', $business->id)->count());

        app(ReviewLoyaltyReceiptClaim::class)->approve($first, $owner, 40_000, Str::uuid()->toString());

        // La segunda sigue pendiente — aprobar la primera no la resolvió.
        $this->assertSame(LoyaltyReceiptClaim::PENDIENTE, $second->fresh()->status);

        app(ReviewLoyaltyReceiptClaim::class)->reject($second, $owner, 'Mismo recibo ya acreditado en otra solicitud.');

        $this->assertSame(LoyaltyReceiptClaim::RECHAZADA, $second->fresh()->status);
        $this->assertSame(1, LoyaltyPurchase::count());
    }

    public function test_a_claim_already_reviewed_cannot_be_reviewed_again(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();
        $claim = $this->submitClaim($business, $customer);

        app(ReviewLoyaltyReceiptClaim::class)->reject($claim, $owner, 'Duplicado.');

        $this->expectException(LoyaltyActionException::class);

        app(ReviewLoyaltyReceiptClaim::class)->approve($claim->fresh(), $owner, 50_000, Str::uuid()->toString());
    }

    public function test_an_employee_from_another_business_cannot_review_a_claim(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        [, $otherOwner] = $this->enrolledBusiness();
        $customer = User::factory()->create();
        $claim = $this->submitClaim($business, $customer);

        $this->expectException(LoyaltyActionException::class);

        app(ReviewLoyaltyReceiptClaim::class)->reject($claim, $otherOwner, 'No es mi negocio.');
    }

    private function submitClaim(mixed $business, User $customer): LoyaltyReceiptClaim
    {
        Storage::fake('private');

        return app(SubmitLoyaltyReceiptClaim::class)->handle(
            $business, $customer, UploadedFile::fake()->image('recibo.jpg'), 'Pagué en efectivo y olvidé mostrar el QR.',
        );
    }
}
