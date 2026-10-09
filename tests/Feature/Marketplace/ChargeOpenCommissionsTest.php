<?php

namespace Tests\Feature\Marketplace;

use App\Domain\Marketplace\Actions\ChargeOpenCommissions;
use App\Domain\Marketplace\Models\CommissionCharge;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Cobro en lote de comisiones abiertas (pendiente #3 de
 * TODO-Marketplace-Checkout.md) — corre semanalmente vía
 * `marketplace:charge-commissions`.
 */
class ChargeOpenCommissionsTest extends TestCase
{
    use RefreshDatabase;

    private function openCharge(string $businessName, int $periodStartDaysAgo, ?string $paymentSourceId = '123'): CommissionCharge
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => $businessName])->business;
        $business->update([
            'wompi_payment_source_id' => $paymentSourceId,
            'auto_renew_enabled' => true,
        ]);

        return CommissionCharge::create([
            'business_id' => $business->id,
            'period_start' => now()->subDays($periodStartDaysAgo)->toDateString(),
            'period_end' => now()->toDateString(),
            'orders_count' => 1,
            'gross_amount_cents' => 5000000,
            'commission_cents' => 250000,
            'status' => CommissionCharge::ABIERTA,
        ]);
    }

    public function test_it_only_charges_charges_open_for_at_least_the_minimum_age(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'com-1', 'status' => 'APPROVED']], 200)]);

        $old = $this->openCharge('Negocio Viejo', periodStartDaysAgo: 10);
        $recent = $this->openCharge('Negocio Reciente', periodStartDaysAgo: 1);

        $count = app(ChargeOpenCommissions::class)->handle(minAgeDays: 7);

        $this->assertSame(1, $count);
        $this->assertSame(CommissionCharge::PAGADA, $old->fresh()->status);
        $this->assertSame(CommissionCharge::ABIERTA, $recent->fresh()->status);
    }

    public function test_min_age_zero_charges_everything_open_regardless_of_age(): void
    {
        Http::fake(['*/transactions' => Http::sequence()
            ->push(['data' => ['id' => 'com-1', 'status' => 'APPROVED']])
            ->push(['data' => ['id' => 'com-2', 'status' => 'APPROVED']]),
        ]);

        $old = $this->openCharge('Negocio Viejo', periodStartDaysAgo: 10);
        $recent = $this->openCharge('Negocio Reciente', periodStartDaysAgo: 1);

        $count = app(ChargeOpenCommissions::class)->handle(minAgeDays: 0);

        $this->assertSame(2, $count);
        $this->assertSame(CommissionCharge::PAGADA, $old->fresh()->status);
        $this->assertSame(CommissionCharge::PAGADA, $recent->fresh()->status);
    }

    public function test_a_business_without_a_saved_card_does_not_halt_the_rest_of_the_batch(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'com-1', 'status' => 'APPROVED']], 200)]);

        $withoutCard = $this->openCharge('Negocio Sin Tarjeta', periodStartDaysAgo: 10, paymentSourceId: null);
        $withCard = $this->openCharge('Negocio Con Tarjeta', periodStartDaysAgo: 10);

        $count = app(ChargeOpenCommissions::class)->handle(minAgeDays: 7);

        $this->assertSame(1, $count);
        $this->assertSame(CommissionCharge::ABIERTA, $withoutCard->fresh()->status);
        $this->assertSame(CommissionCharge::PAGADA, $withCard->fresh()->status);
    }

    public function test_charges_with_zero_commission_are_skipped(): void
    {
        $zero = $this->openCharge('Negocio Cero', periodStartDaysAgo: 10);
        $zero->update(['commission_cents' => 0]);

        $count = app(ChargeOpenCommissions::class)->handle(minAgeDays: 7);

        $this->assertSame(0, $count);
        $this->assertSame(CommissionCharge::ABIERTA, $zero->fresh()->status);
    }

    public function test_the_artisan_command_charges_open_commissions(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'com-1', 'status' => 'APPROVED']], 200)]);

        $old = $this->openCharge('Negocio Comando', periodStartDaysAgo: 10);

        $this->artisan('marketplace:charge-commissions')
            ->expectsOutput('Comisiones cobradas: 1.')
            ->assertSuccessful();

        $this->assertSame(CommissionCharge::PAGADA, $old->fresh()->status);
    }

    public function test_the_all_flag_ignores_the_minimum_age(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'com-1', 'status' => 'APPROVED']], 200)]);

        $recent = $this->openCharge('Negocio Reciente Comando', periodStartDaysAgo: 1);

        $this->artisan('marketplace:charge-commissions --all')
            ->expectsOutput('Comisiones cobradas: 1.')
            ->assertSuccessful();

        $this->assertSame(CommissionCharge::PAGADA, $recent->fresh()->status);
    }

    /**
     * PR2 de TODO_VENTAS_RENTABILIDAD.md (hallazgo #4, decisión del
     * usuario 2026-10-09 "reintentar automático con backoff"): un cobro
     * rechazado queda `fallida` con el primer reintento programado a 2
     * días — el mismo lote NO debe volver a tocarla antes de esa fecha.
     */
    public function test_a_declined_charge_is_scheduled_for_retry_and_not_picked_up_before_its_date(): void
    {
        Http::fake(['*/transactions' => Http::response(['data' => ['id' => 'com-1', 'status' => 'DECLINED']], 200)]);

        $charge = $this->openCharge('Negocio Rechazado', periodStartDaysAgo: 10);

        $count = app(ChargeOpenCommissions::class)->handle(minAgeDays: 7);

        $this->assertSame(0, $count);
        $charge->refresh();
        $this->assertSame(CommissionCharge::FALLIDA, $charge->status);
        $this->assertSame(1, $charge->retry_count);
        $this->assertTrue($charge->next_retry_at->isSameDay(now()->addDays(2)));

        // Correr el lote otra vez de inmediato no debe reintentarla —
        // todavía no llegó la fecha programada.
        $secondCount = app(ChargeOpenCommissions::class)->handle(minAgeDays: 7);
        $this->assertSame(0, $secondCount);
    }

    public function test_a_failed_charge_past_its_retry_date_is_retried_and_can_succeed(): void
    {
        // `Http::fake()` llamado dos veces NO sobrescribe la regla
        // anterior para el mismo patrón de URL — para que cada llamada
        // a `chargePaymentSource` reciba una respuesta distinta hay que
        // encadenarlas con `Http::sequence()`, igual que ya hace
        // `test_min_age_zero_charges_everything_open_regardless_of_age`.
        Http::fake(['*/transactions' => Http::sequence()
            ->push(['data' => ['id' => 'com-1', 'status' => 'DECLINED']])
            ->push(['data' => ['id' => 'com-2', 'status' => 'APPROVED']]),
        ]);
        $charge = $this->openCharge('Negocio Reintento', periodStartDaysAgo: 10);
        app(ChargeOpenCommissions::class)->handle(minAgeDays: 7);
        $this->assertSame(CommissionCharge::FALLIDA, $charge->fresh()->status);

        $this->travel(3)->days();

        $count = app(ChargeOpenCommissions::class)->handle(minAgeDays: 7);

        $this->assertSame(1, $count);
        $this->assertSame(CommissionCharge::PAGADA, $charge->fresh()->status);
    }

    public function test_after_exhausting_every_retry_the_charge_needs_manual_attention_and_stops_being_picked_up(): void
    {
        Http::fake(['*/transactions' => Http::sequence()
            ->push(['data' => ['id' => 'com-1', 'status' => 'DECLINED']])
            ->push(['data' => ['id' => 'com-2', 'status' => 'DECLINED']])
            ->push(['data' => ['id' => 'com-3', 'status' => 'DECLINED']])
            ->push(['data' => ['id' => 'com-4', 'status' => 'DECLINED']]),
        ]);
        $charge = $this->openCharge('Negocio Sin Suerte', periodStartDaysAgo: 10);

        // Backoff: 2, 5 y 10 días — tres reintentos antes de agotarse.
        app(ChargeOpenCommissions::class)->handle(minAgeDays: 7);
        $this->travel(2)->days();
        app(ChargeOpenCommissions::class)->handle(minAgeDays: 7);
        $this->travel(5)->days();
        app(ChargeOpenCommissions::class)->handle(minAgeDays: 7);
        $this->travel(10)->days();
        app(ChargeOpenCommissions::class)->handle(minAgeDays: 7);

        $charge->refresh();
        $this->assertSame(CommissionCharge::FALLIDA, $charge->status);
        $this->assertSame(4, $charge->retry_count);
        $this->assertNull($charge->next_retry_at);
        $this->assertTrue($charge->needsManualAttention());

        // Ya agotó los reintentos automáticos — ni avanzando más tiempo
        // el lote vuelve a tocarla sola.
        $this->travel(30)->days();
        $count = app(ChargeOpenCommissions::class)->handle(minAgeDays: 7);
        $this->assertSame(0, $count);
    }
}
