<?php

namespace App\Domain\Loyalty\Support;

/**
 * Corrección completa de la lógica de recomendación de Merkapuntos
 * (TODO_Correccion_Logica_Merkapuntos.md). La lógica anterior usaba el
 * costo del premio como referencia directa (`costo / fracción`), lo que
 * producía sugerencias de miles de puntos para premios baratos — un
 * premio de $8.000 llegaba a sugerir 1.067–1.600 puntos, inalcanzable
 * con la regla de 1 punto por cada $1.000 COP.
 *
 * La nueva lógica responde a lo que el TODO pide: "¿cuánto gasta
 * normalmente un cliente?" × "¿cuántas compras quieres que haga antes
 * del premio?" — el costo del premio solo se usa DESPUÉS, para mostrar
 * qué tan caro es el incentivo frente a ese gasto (el "porcentaje de
 * recompensa"), nunca para fijar los puntos directamente.
 *
 * Clase pura, sin acceso a base de datos — "la vista únicamente debe
 * consumir los resultados del servicio" (TODO, sección Arquitectura).
 * Quien la llama (`CalculateLoyaltyRewardSuggestion`) es responsable de
 * resolver la política de acumulación real del negocio
 * (`points_per_unit`/`unit_cents`) antes de invocar estos métodos — así
 * esta clase nunca depende de qué regla de acumulación tenga cada
 * negocio ni de la configuración global.
 */
class MerkapuntosRewardCalculator
{
    /**
     * gasto_objetivo = ticket_promedio × compras_objetivo
     */
    public function calculateTargetSpend(int $averageTicketCents, int $targetPurchases): int
    {
        return max(0, $averageTicketCents) * max(0, $targetPurchases);
    }

    /**
     * puntos_requeridos = gasto_objetivo / unidad_de_acumulación, con la
     * regla real de acumulación del negocio (no un valor fijo quemado).
     */
    public function calculateRequiredPoints(int $targetSpendCents, int $pointsPerUnit, int $unitCents): int
    {
        if ($pointsPerUnit <= 0 || $unitCents <= 0 || $targetSpendCents <= 0) {
            return 0;
        }

        return (int) ceil($targetSpendCents / $unitCents) * $pointsPerUnit;
    }

    /**
     * Inverso de `calculateRequiredPoints()`: cuánto gasto representan
     * N puntos ya elegidos — lo necesita el "valor manual" del TODO para
     * recalcular en tiempo real cuando el comerciante escribe los
     * puntos a mano en vez de elegir una de las tres opciones.
     */
    public function calculateRequiredSpend(int $points, int $pointsPerUnit, int $unitCents): int
    {
        if ($pointsPerUnit <= 0 || $unitCents <= 0 || $points <= 0) {
            return 0;
        }

        return (int) ceil($points / $pointsPerUnit) * $unitCents;
    }

    /**
     * porcentaje_recompensa = costo_real_premio / gasto_objetivo × 100
     */
    public function calculateRewardPercentage(int $fullCostCents, int $targetSpendCents): float
    {
        if ($targetSpendCents <= 0) {
            return 0.0;
        }

        return round(($fullCostCents / $targetSpendCents) * 100, 2);
    }

    /**
     * Número aproximado de compras equivalentes a un gasto dado, según
     * el ticket promedio del negocio — se redondea a 1 decimal, la
     * vista lo presenta como rango amigable ("≈ 7–8 compras").
     */
    public function calculateEstimatedPurchases(int $spendCents, int $averageTicketCents): float
    {
        if ($averageTicketCents <= 0) {
            return 0.0;
        }

        return round($spendCents / $averageTicketCents, 1);
    }

    /**
     * Las tres opciones automáticas del asistente (4, 6 y 8 compras por
     * defecto — configurable en `config('loyalty.reward_assistant')`).
     *
     * @return list<array{purchases: int, points: int, target_spend_cents: int, percentage: float, warnings: list<string>}>
     */
    public function generateRecommendations(int $fullCostCents, int $averageTicketCents, int $pointsPerUnit, int $unitCents): array
    {
        $targetPurchaseOptions = config('loyalty.reward_assistant.target_purchase_options', [4, 6, 8]);

        $recommendations = [];

        foreach ($targetPurchaseOptions as $purchases) {
            $targetSpend = $this->calculateTargetSpend($averageTicketCents, $purchases);
            $points = $this->calculateRequiredPoints($targetSpend, $pointsPerUnit, $unitCents);
            $percentage = $this->calculateRewardPercentage($fullCostCents, $targetSpend);

            $recommendations[] = [
                'purchases' => $purchases,
                'points' => $points,
                'target_spend_cents' => $targetSpend,
                'percentage' => $percentage,
                'warnings' => $this->validateRewardConfiguration((float) $purchases, $percentage),
            ];
        }

        return $recommendations;
    }

    /**
     * Advertencias que NUNCA bloquean el guardado (TODO: "No bloquear
     * el guardado; únicamente advertir"), solo informan.
     *
     * @return list<string>
     */
    public function validateRewardConfiguration(float $estimatedPurchases, float $percentage): array
    {
        $warnings = [];

        if ($estimatedPurchases > (float) config('loyalty.reward_assistant.max_recommended_purchases')) {
            $warnings[] = 'Esta recompensa puede ser difícil de alcanzar para tus clientes.';
        }

        if ($percentage > (float) config('loyalty.reward_assistant.max_recommended_percentage')) {
            $warnings[] = 'Esta recompensa puede representar un costo alto para tu negocio.';
        }

        return $warnings;
    }
}
