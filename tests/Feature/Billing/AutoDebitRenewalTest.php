<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Actions\ApplyPlanDowngrades;
use App\Domain\Billing\Actions\ProcessSubscriptionRenewals;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Notifications\PaymentFailed;
use App\Domain\Billing\Notifications\SubscriptionRenewalDue;
use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * 4.2 del TODO: débito automático real de la renovación mensual (Wompi
 * "fuentes de pago") y el arreglo del bug donde una suscripción `activa`
 * vencida nunca bajaba de plan porque `ApplyPlanDowngrades` solo miraba
 * `cancelada`/`en_gracia` sin que nada las pusiera ahí.
 */
class AutoDebitRenewalTest extends TestCase
{
    use RefreshDatabase;

    private function paidPlan(): Plan
    {
        return Plan::create([
            'slug' => 'emprendedor',
            'name' => 'Emprendedor',
            'description' => 'Plan de pago.',
            'price_cents' => 1990000,
            'billing_period' => Plan::MENSUAL,
            'limits' => ['max_products' => null, 'max_members' => 5, 'max_featured_days' => 7],
            'trial_days' => 0,
            'is_active' => true,
            'position' => 1,
        ]);
    }

    private function freePlan(): Plan
    {
        return Plan::firstOrCreate(['slug' => 'gratis'], [
            'name' => 'Gratis',
            'description' => 'Plan gratuito.',
            'price_cents' => null,
            'billing_period' => null,
            'limits' => ['max_products' => 10, 'max_members' => 1],
            'trial_days' => 0,
            'is_active' => true,
            'position' => 0,
        ]);
    }

    private function businessOnExpiredPlan(Plan $plan, bool $withCard = false): Business
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Vencido'])->business;

        if ($withCard) {
            $business->update([
                'wompi_payment_source_id' => '3891',
                'card_brand' => 'VISA',
                'card_last_four' => '4242',
                'auto_renew_enabled' => true,
            ]);
        }

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => Subscription::ACTIVA,
            'current_period_starts_at' => now()->subMonth(),
            'current_period_ends_at' => now()->subDay(),
        ]);

        return $business->fresh();
    }

    public function test_expired_active_subscription_without_a_card_enters_grace_period_and_notifies(): void
    {
        Notification::fake();

        $plan = $this->paidPlan();
        $business = $this->businessOnExpiredPlan($plan);

        app(ProcessSubscriptionRenewals::class)->handle();

        $subscription = $business->fresh()->subscription;
        $this->assertSame(Subscription::EN_GRACIA, $subscription->status);
        $this->assertNotNull($subscription->grace_ends_at);
        $this->assertSame('emprendedor', $business->fresh()->activePlan()->slug);

        Notification::assertSentTo($business->fresh()->members->first(), SubscriptionRenewalDue::class);
    }

    public function test_expired_active_subscription_with_a_saved_card_is_charged_automatically(): void
    {
        $plan = $this->paidPlan();
        $business = $this->businessOnExpiredPlan($plan, withCard: true);

        Http::fake(['*/transactions' => Http::response([
            'data' => ['id' => 'wompi-renov-1', 'status' => 'APPROVED'],
        ], 200)]);

        app(ProcessSubscriptionRenewals::class)->handle();

        $business = $business->fresh();
        $this->assertSame('emprendedor', $business->activePlan()->slug);
        $this->assertSame(Subscription::ACTIVA, $business->subscription->status);
        $this->assertTrue($business->subscription->current_period_ends_at->isFuture());

        $payment = Payment::where('business_id', $business->id)->latest()->first();
        $this->assertSame(Payment::APROBADO, $payment->status);
        $this->assertSame('wompi-renov-1', $payment->wompi_transaction_id);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/transactions')
            && $request['payment_source_id'] === 3891
        );
    }

    /**
     * `SubscribeToPlan` nunca actualiza la fila vieja de `Subscription` al
     * renovar, siempre crea una nueva y deja la anterior con su fecha
     * vencida (para conservar historial). Bug real encontrado probando en
     * vivo contra el sandbox de Wompi: si `ProcessSubscriptionRenewals`
     * consulta `Subscription` sin filtrar por la vigente, esa fila vieja
     * "activa pero vencida" se vuelve a cobrar en cada corrida — cobro
     * duplicado infinito. Cubre que un negocio se cobre exactamente una
     * vez por ciclo, incluso corriendo el comando varias veces el mismo
     * día y en meses sucesivos.
     */
    public function test_renewal_charges_exactly_once_per_cycle_even_with_stale_subscription_rows(): void
    {
        $plan = $this->paidPlan();
        $business = $this->businessOnExpiredPlan($plan, withCard: true);

        Http::fake(['*/transactions' => Http::sequence()
            ->push(['data' => ['id' => 'wompi-renov-cycle-1', 'status' => 'APPROVED']], 200)
            ->push(['data' => ['id' => 'wompi-renov-cycle-2', 'status' => 'APPROVED']], 200),
        ]);

        $firstRunCount = app(ProcessSubscriptionRenewals::class)->handle();
        $this->assertSame(1, $firstRunCount);
        $this->assertSame(1, Payment::where('business_id', $business->id)->count());

        // Repetir la misma corrida el mismo día no debe volver a cobrar,
        // aunque la fila vieja siga en la BD con su fecha vencida.
        $secondRunCount = app(ProcessSubscriptionRenewals::class)->handle();
        $this->assertSame(0, $secondRunCount);
        $this->assertSame(1, Payment::where('business_id', $business->id)->count());

        // Un mes después vence la suscripción vigente (la que creó el
        // primer cobro) — debe cobrar una vez más, ni más ni menos.
        $business->fresh()->subscription->update(['current_period_ends_at' => now()->subDay()]);

        $thirdRunCount = app(ProcessSubscriptionRenewals::class)->handle();
        $this->assertSame(1, $thirdRunCount);
        $this->assertSame(2, Payment::where('business_id', $business->id)->count());
    }

    public function test_declined_automatic_charge_enters_grace_period_and_notifies_payment_failed(): void
    {
        Notification::fake();

        $plan = $this->paidPlan();
        $business = $this->businessOnExpiredPlan($plan, withCard: true);

        Http::fake(['*/transactions' => Http::response([
            'data' => ['id' => 'wompi-renov-2', 'status' => 'DECLINED'],
        ], 200)]);

        app(ProcessSubscriptionRenewals::class)->handle();

        $business = $business->fresh();
        $this->assertSame(Subscription::EN_GRACIA, $business->subscription->status);

        Notification::assertSentTo($business->members->first(), PaymentFailed::class);
    }

    public function test_downgrade_only_happens_after_grace_period_actually_ends(): void
    {
        $plan = $this->paidPlan();
        $this->freePlan();

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio En Gracia'])->business;

        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => Subscription::EN_GRACIA,
            'current_period_starts_at' => now()->subMonth(),
            'current_period_ends_at' => now()->subDays(2),
            'grace_ends_at' => now()->addDay(),
        ]);

        app(ApplyPlanDowngrades::class)->handle();

        $this->assertSame('emprendedor', $business->fresh()->activePlan()->slug, 'No debería bajar de plan mientras la gracia sigue vigente.');

        $subscription->update(['grace_ends_at' => now()->subDay()]);

        app(ApplyPlanDowngrades::class)->handle();

        $this->assertSame('gratis', $business->fresh()->activePlan()->slug, 'Debe bajar a Gratis una vez pasada la gracia.');
    }

    public function test_cancelled_subscription_still_downgrades_once_its_paid_period_ends(): void
    {
        $plan = $this->paidPlan();
        $this->freePlan();

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Cancelado'])->business;

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => Subscription::CANCELADA,
            'current_period_starts_at' => now()->subMonth(),
            'current_period_ends_at' => now()->subDay(),
            'cancelled_at' => now()->subDays(5),
        ]);

        app(ApplyPlanDowngrades::class)->handle();

        $this->assertSame('gratis', $business->fresh()->activePlan()->slug);
    }

    public function test_saving_a_card_creates_a_payment_source_and_stores_it_on_the_business(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Tarjeta'])->business;

        Http::fake(['*/payment_sources' => Http::response([
            'data' => ['id' => 3891, 'status' => 'AVAILABLE'],
        ], 201)]);

        $this->actingAs($owner)->postJson(
            route('emprendedores.negocios.plan.tarjeta.store', $business),
            [
                'card_token' => 'tok_test_123',
                'card_brand' => 'VISA',
                'card_last_four' => '4242',
                'customer_email' => $owner->email,
                'acceptance_token' => 'accept-token',
                'accept_personal_auth_token' => 'personal-auth-token',
            ],
        )->assertOk();

        $business = $business->fresh();
        $this->assertSame('3891', $business->wompi_payment_source_id);
        $this->assertSame('VISA', $business->card_brand);
        $this->assertSame('4242', $business->card_last_four);
        $this->assertTrue($business->auto_renew_enabled);
    }

    public function test_someone_who_cannot_manage_the_business_cannot_save_a_card(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Ajeno'])->business;

        $this->actingAs(User::factory()->create())->postJson(
            route('emprendedores.negocios.plan.tarjeta.store', $business),
            [
                'card_token' => 'tok_test_123',
                'card_brand' => 'VISA',
                'card_last_four' => '4242',
                'customer_email' => $owner->email,
                'acceptance_token' => 'accept-token',
                'accept_personal_auth_token' => 'personal-auth-token',
            ],
        )->assertForbidden();
    }

    public function test_removing_the_saved_card_clears_it_from_the_business(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Quita Tarjeta'])->business;
        $business->update([
            'wompi_payment_source_id' => '3891',
            'card_brand' => 'VISA',
            'card_last_four' => '4242',
            'auto_renew_enabled' => true,
        ]);

        $this->actingAs($owner)->deleteJson(route('emprendedores.negocios.plan.tarjeta.destroy', $business))
            ->assertOk();

        $business = $business->fresh();
        $this->assertNull($business->wompi_payment_source_id);
        $this->assertFalse($business->auto_renew_enabled);
    }
}
