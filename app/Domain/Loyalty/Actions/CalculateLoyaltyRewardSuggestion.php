<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyPolicy;
use App\Domain\Loyalty\Support\MerkapuntosRewardCalculator;

/**
 * Orquesta el asistente de recompensa (TODO_Correccion_Logica_Merkapuntos.md):
 * resuelve la regla de acumulación REAL del negocio y delega toda la
 * matemática a `MerkapuntosRewardCalculator` (servicio puro, sin acceso
 * a base de datos). "La lógica queda centralizada en un
 * servicio/clase reutilizable" — esta Action es la única responsable de
 * tocar el modelo `LoyaltyPolicy`; el calculador nunca lo hace.
 *
 * Corrige la lógica anterior, que usaba el costo del premio como
 * referencia directa (`costo / fracción`) y producía sugerencias de
 * miles de puntos para premios baratos. Ahora el costo del premio solo
 * se usa para mostrar qué tan caro es el incentivo frente al gasto
 * objetivo (ticket promedio × compras objetivo) — nunca para fijar los
 * puntos.
 */
class CalculateLoyaltyRewardSuggestion
{
    public function __construct(private readonly MerkapuntosRewardCalculator $calculator) {}

    /**
     * @return array{
     *     recommendations: list<array{purchases: int, points: int, target_spend_cents: int, percentage: float, warnings: list<string>}>,
     *     full_cost_cents: int,
     *     average_ticket_cents: int,
     *     points_per_unit: int,
     *     unit_cents: int,
     *     policy_version: int,
     * }
     *
     * @throws LoyaltyActionException
     */
    public function handle(Business $business, int $fullCostCents, int $averageTicketCents): array
    {
        if ($fullCostCents <= 0) {
            throw new LoyaltyActionException('Indica el costo real del premio (mayor que cero) para calcular una sugerencia.');
        }

        if ($averageTicketCents <= 0) {
            throw new LoyaltyActionException('Indica cuánto gasta normalmente un cliente en tu negocio (mayor que cero) para calcular una sugerencia.');
        }

        $policy = $business->loyaltyPolicies()->where('status', LoyaltyPolicy::ACTIVA)->first();

        if (! $policy) {
            throw new LoyaltyActionException('Este negocio no tiene una política de acumulación activa todavía.');
        }

        if (($policy->rule['type'] ?? null) !== 'simple') {
            throw new LoyaltyActionException('La sugerencia automática solo está disponible para la regla "simple" por ahora.');
        }

        $pointsPerUnit = (int) ($policy->rule['points_per_unit'] ?? 0);
        $unitCents = (int) ($policy->rule['unit_cents'] ?? 0);

        if ($pointsPerUnit <= 0 || $unitCents <= 0) {
            throw new LoyaltyActionException('La política de acumulación de este negocio no tiene una regla válida.');
        }

        $recommendations = $this->calculator->generateRecommendations($fullCostCents, $averageTicketCents, $pointsPerUnit, $unitCents);

        return [
            'recommendations' => $recommendations,
            'full_cost_cents' => $fullCostCents,
            'average_ticket_cents' => $averageTicketCents,
            'points_per_unit' => $pointsPerUnit,
            'unit_cents' => $unitCents,
            'policy_version' => $policy->version,
        ];
    }
}
