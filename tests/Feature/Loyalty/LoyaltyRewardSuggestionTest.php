<?php

namespace Tests\Feature\Loyalty;

use App\Domain\Loyalty\Actions\CalculateLoyaltyRewardSuggestion;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Loyalty\Concerns\SetsUpLoyaltyBusiness;
use Tests\TestCase;

/**
 * TODO_Correccion_Logica_Merkapuntos.md — "caso de prueba obligatorio":
 * un premio de $8.000 con ticket promedio de $20.000 nunca debe volver
 * a sugerir algo como 1.067 o 1.600 puntos (el bug de la lógica
 * anterior, que usaba el costo del premio como referencia directa). La
 * nueva lógica se calcula desde ticket promedio × compras objetivo.
 */
class LoyaltyRewardSuggestionTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpLoyaltyBusiness;

    public function test_matches_the_mandatory_worked_example_from_the_todo_document(): void
    {
        // Regla: 1 Merkapunto por cada $1.000 COP (100.000 centavos).
        [$business] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 100_000);

        $result = app(CalculateLoyaltyRewardSuggestion::class)->handle(
            $business, fullCostCents: 800_000, averageTicketCents: 2_000_000,
        );

        $recommendations = collect($result['recommendations'])->keyBy('purchases');

        $this->assertSame(80, $recommendations[4]['points']);
        $this->assertSame(8_000_000, $recommendations[4]['target_spend_cents']);
        $this->assertSame(10.0, $recommendations[4]['percentage']);

        $this->assertSame(120, $recommendations[6]['points']);
        $this->assertSame(12_000_000, $recommendations[6]['target_spend_cents']);
        $this->assertSame(6.67, $recommendations[6]['percentage']);

        $this->assertSame(160, $recommendations[8]['points']);
        $this->assertSame(16_000_000, $recommendations[8]['target_spend_cents']);
        $this->assertSame(5.0, $recommendations[8]['percentage']);

        // Nunca debería volver a sugerir algo como 1.067 o 1.600 puntos.
        foreach ($result['recommendations'] as $option) {
            $this->assertLessThan(1000, $option['points']);
        }
    }

    public function test_rejects_a_zero_or_negative_cost(): void
    {
        [$business] = $this->enrolledBusiness();

        $this->expectException(LoyaltyActionException::class);

        app(CalculateLoyaltyRewardSuggestion::class)->handle($business, 0, 2_000_000);
    }

    public function test_rejects_a_zero_or_negative_average_ticket(): void
    {
        [$business] = $this->enrolledBusiness();

        $this->expectException(LoyaltyActionException::class);

        app(CalculateLoyaltyRewardSuggestion::class)->handle($business, 800_000, 0);
    }

    public function test_requires_an_active_simple_policy(): void
    {
        [$business] = $this->enrolledBusiness();
        $business->loyaltyPolicies()->update(['status' => 'archivada']);

        $this->expectException(LoyaltyActionException::class);

        app(CalculateLoyaltyRewardSuggestion::class)->handle($business, 800_000, 2_000_000);
    }

    public function test_an_expensive_reward_relative_to_target_spend_is_flagged_but_not_blocked(): void
    {
        // Premio muy caro frente al gasto objetivo → porcentaje por
        // encima del umbral configurado (12%).
        [$business] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 100_000);

        $result = app(CalculateLoyaltyRewardSuggestion::class)->handle(
            $business, fullCostCents: 3_000_000, averageTicketCents: 2_000_000,
        );

        $recommendations = collect($result['recommendations'])->keyBy('purchases');

        $this->assertNotEmpty($recommendations[4]['warnings']);
        $this->assertStringContainsString('costo alto', $recommendations[4]['warnings'][0]);
    }
}
