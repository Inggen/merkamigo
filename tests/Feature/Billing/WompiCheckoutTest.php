<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Actions\RefundPayment;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Notifications\PaymentFailed;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use App\Support\Wompi\WompiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * 4.2 del TODO: checkout de Wompi (sandbox) — firma de integridad,
 * verificación contra la API al volver del pago, webhook con verificación
 * de firma e idempotencia, y reembolso restringido a superadmin.
 */
class WompiCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function assignPlatformRole(User $user, string $role): void
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');
        $user->assignRole(Role::findOrCreate($role, 'web'));

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');
    }

    private function paidPlan(): Plan
    {
        return Plan::create([
            'slug' => 'emprendedor',
            'name' => 'Emprendedor',
            'description' => 'Plan de pago.',
            'price_cents' => 1990000,
            'billing_period' => Plan::MENSUAL,
            'limits' => ['max_products' => null, 'max_members' => 5, 'max_featured_days' => 7],
            'trial_days' => 14,
            'is_active' => true,
            'position' => 1,
        ]);
    }

    public function test_starting_checkout_for_a_paid_plan_signs_the_payment_correctly(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Pago'])->business;
        $plan = $this->paidPlan();

        $response = $this->actingAs($owner)
            ->get(route('emprendedores.negocios.plan.checkout', ['business' => $business, 'plan' => $plan]))
            ->assertOk();

        $payment = Payment::where('business_id', $business->id)->firstOrFail();

        $this->assertSame($plan->id, $payment->plan_id);
        $this->assertSame(1990000, $payment->amount_cents);
        $this->assertSame(Payment::PENDIENTE, $payment->status);

        $expectedSignature = app(WompiClient::class)->integritySignature($payment->reference, $payment->amount_cents, $payment->currency);
        $response->assertSee($expectedSignature);
        $response->assertSee($payment->reference);
    }

    public function test_someone_who_cannot_manage_the_business_cannot_start_a_checkout(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Ajeno'])->business;
        $plan = $this->paidPlan();

        $this->actingAs(User::factory()->create())
            ->get(route('emprendedores.negocios.plan.checkout', ['business' => $business, 'plan' => $plan]))
            ->assertForbidden();
    }

    public function test_returning_from_checkout_with_an_approved_transaction_activates_the_plan(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Aprobado'])->business;
        $plan = $this->paidPlan();

        $payment = Payment::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'reference' => 'MKA-TEST-APPROVED',
            'amount_cents' => 1990000,
            'currency' => 'COP',
            'status' => Payment::PENDIENTE,
        ]);

        Http::fake([
            '*/transactions/wompi-txn-1' => Http::response([
                'data' => [
                    'id' => 'wompi-txn-1',
                    'status' => 'APPROVED',
                    'reference' => $payment->reference,
                ],
            ], 200),
        ]);

        $this->get(route('billing.checkout.return', ['id' => 'wompi-txn-1']))
            ->assertOk()
            ->assertSee('¡Pago aprobado!');

        $payment->refresh();
        $this->assertSame(Payment::APROBADO, $payment->status);
        $this->assertSame('wompi-txn-1', $payment->wompi_transaction_id);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('emprendedor', $business->fresh()->activePlan()->slug);
    }

    public function test_returning_from_checkout_with_a_declined_transaction_notifies_the_business(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Rechazado'])->business;
        $plan = $this->paidPlan();

        $payment = Payment::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'reference' => 'MKA-TEST-DECLINED',
            'amount_cents' => 1990000,
            'currency' => 'COP',
            'status' => Payment::PENDIENTE,
        ]);

        Http::fake([
            '*/transactions/wompi-txn-2' => Http::response([
                'data' => [
                    'id' => 'wompi-txn-2',
                    'status' => 'DECLINED',
                    'reference' => $payment->reference,
                ],
            ], 200),
        ]);

        $this->get(route('billing.checkout.return', ['id' => 'wompi-txn-2']))
            ->assertOk()
            ->assertSee('El pago no fue aprobado');

        $payment->refresh();
        $this->assertSame(Payment::RECHAZADO, $payment->status);
        $this->assertSame('gratis', $business->fresh()->activePlan()->slug);

        Notification::assertSentTo($owner, PaymentFailed::class);
    }

    public function test_webhook_rejects_events_with_an_invalid_signature(): void
    {
        $response = $this->postJson('/webhooks/wompi', [
            'event' => 'transaction.updated',
            'data' => ['transaction' => ['id' => 'x', 'status' => 'APPROVED', 'reference' => 'MKA-NONE']],
            'signature' => ['properties' => ['data.transaction.id'], 'checksum' => 'not-the-real-checksum'],
            'timestamp' => now()->timestamp,
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_with_a_valid_signature_approves_the_matching_payment_and_is_idempotent(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Webhook'])->business;
        $plan = $this->paidPlan();

        $payment = Payment::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'reference' => 'MKA-TEST-WEBHOOK',
            'amount_cents' => 1990000,
            'currency' => 'COP',
            'status' => Payment::PENDIENTE,
        ]);

        $timestamp = now()->timestamp;
        $transactionId = 'wompi-txn-webhook';
        $checksum = hash('sha256', $transactionId.'APPROVED'.$timestamp.config('services.wompi.events_secret'));

        $event = [
            'event' => 'transaction.updated',
            'data' => [
                'transaction' => [
                    'id' => $transactionId,
                    'status' => 'APPROVED',
                    'reference' => $payment->reference,
                ],
            ],
            'signature' => [
                'properties' => ['data.transaction.id', 'data.transaction.status'],
                'checksum' => $checksum,
            ],
            'timestamp' => $timestamp,
        ];

        $this->postJson('/webhooks/wompi', $event)->assertOk();

        $payment->refresh();
        $this->assertSame(Payment::APROBADO, $payment->status);
        $this->assertDatabaseCount('wompi_webhook_events', 1);

        // Repetir el mismo evento (Wompi reintenta) no debe reprocesar ni duplicar el registro.
        $this->postJson('/webhooks/wompi', $event)->assertOk();
        $this->assertDatabaseCount('wompi_webhook_events', 1);
    }

    public function test_refund_is_only_available_to_a_superadmin_and_calls_the_wompi_void_endpoint(): void
    {
        Http::fake(['*/transactions/*/void' => Http::response([], 200)]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Reembolso'])->business;
        $plan = $this->paidPlan();

        $payment = Payment::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'reference' => 'MKA-TEST-REFUND',
            'wompi_transaction_id' => 'wompi-txn-refund',
            'amount_cents' => 1990000,
            'currency' => 'COP',
            'status' => Payment::APROBADO,
            'paid_at' => now(),
        ]);

        $superadmin = User::factory()->create();
        $this->assignPlatformRole($superadmin, 'superadmin');

        app(RefundPayment::class)->handle($payment, $superadmin);

        $payment->refresh();
        $this->assertSame(Payment::REEMBOLSADO, $payment->status);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/transactions/wompi-txn-refund/void'));
    }

    public function test_refunding_a_payment_that_is_not_approved_is_rejected(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Pendiente'])->business;
        $plan = $this->paidPlan();

        $payment = Payment::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'reference' => 'MKA-TEST-PENDING',
            'amount_cents' => 1990000,
            'currency' => 'COP',
            'status' => Payment::PENDIENTE,
        ]);

        $superadmin = User::factory()->create();
        $this->assignPlatformRole($superadmin, 'superadmin');

        $this->expectException(\InvalidArgumentException::class);

        app(RefundPayment::class)->handle($payment, $superadmin);
    }
}
