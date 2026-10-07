<?php

namespace Tests\Unit\Loyalty;

use App\Domain\Loyalty\Support\MerkapuntosRewardCalculator;
use Tests\TestCase;

/**
 * TODO_Correccion_Logica_Merkapuntos.md — pruebas puras de la fórmula
 * (sin negocio, sin política, sin base de datos): "caso de prueba
 * obligatorio" del propio documento.
 */
class MerkapuntosRewardCalculatorTest extends TestCase
{
    private function calculator(): MerkapuntosRewardCalculator
    {
        return new MerkapuntosRewardCalculator;
    }

    public function test_target_spend_is_average_ticket_times_target_purchases(): void
    {
        $this->assertSame(12_000_000, $this->calculator()->calculateTargetSpend(2_000_000, 6));
    }

    public function test_required_points_uses_the_businesss_real_accrual_rule(): void
    {
        // 1 punto por cada $1.000 COP (100.000 centavos).
        $this->assertSame(120, $this->calculator()->calculateRequiredPoints(12_000_000, 1, 100_000));
    }

    public function test_required_points_rounds_up_partial_units(): void
    {
        // 100.050 centavos de gasto ÷ 100.000 = 1,0005 → se redondea hacia
        // arriba a 2 unidades, nunca se regala una fracción de unidad.
        $this->assertSame(2, $this->calculator()->calculateRequiredPoints(100_050, 1, 100_000));
    }

    public function test_required_spend_is_the_inverse_of_required_points(): void
    {
        $this->assertSame(12_000_000, $this->calculator()->calculateRequiredSpend(120, 1, 100_000));
    }

    public function test_reward_percentage_is_cost_over_target_spend(): void
    {
        $this->assertSame(6.67, $this->calculator()->calculateRewardPercentage(800_000, 12_000_000));
    }

    public function test_reward_percentage_is_zero_when_target_spend_is_zero(): void
    {
        $this->assertSame(0.0, $this->calculator()->calculateRewardPercentage(800_000, 0));
    }

    public function test_estimated_purchases_divides_spend_by_average_ticket(): void
    {
        // Ejemplo del "valor manual" del TODO: 150 puntos, ticket $20.000
        // → gasto $150.000 → 7,5 compras.
        $this->assertSame(7.5, $this->calculator()->calculateEstimatedPurchases(15_000_000, 2_000_000));
    }

    /**
     * El "caso de prueba obligatorio" exacto del TODO: costo $8.000,
     * ticket promedio $20.000 → 4/6/8 compras → 80/120/160 puntos →
     * 10% / 6,67% / 5% de incentivo. Nunca 1.067 ni 1.600 puntos.
     */
    public function test_generate_recommendations_matches_the_mandatory_example(): void
    {
        $recommendations = collect($this->calculator()->generateRecommendations(800_000, 2_000_000, 1, 100_000))
            ->keyBy('purchases');

        $this->assertSame(80, $recommendations[4]['points']);
        $this->assertSame(8_000_000, $recommendations[4]['target_spend_cents']);
        $this->assertSame(10.0, $recommendations[4]['percentage']);
        $this->assertSame([], $recommendations[4]['warnings']);

        $this->assertSame(120, $recommendations[6]['points']);
        $this->assertSame(12_000_000, $recommendations[6]['target_spend_cents']);
        $this->assertSame(6.67, $recommendations[6]['percentage']);

        $this->assertSame(160, $recommendations[8]['points']);
        $this->assertSame(16_000_000, $recommendations[8]['target_spend_cents']);
        $this->assertSame(5.0, $recommendations[8]['percentage']);
    }

    public function test_validate_warns_when_too_many_purchases_are_required_but_never_blocks(): void
    {
        $warnings = $this->calculator()->validateRewardConfiguration(13.0, 5.0);

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('difícil de alcanzar', $warnings[0]);
    }

    public function test_validate_warns_when_the_percentage_is_too_high_but_never_blocks(): void
    {
        $warnings = $this->calculator()->validateRewardConfiguration(4.0, 15.0);

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('costo alto', $warnings[0]);
    }

    public function test_validate_returns_no_warnings_for_a_healthy_configuration(): void
    {
        $this->assertSame([], $this->calculator()->validateRewardConfiguration(6.0, 6.67));
    }
}
