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
}
