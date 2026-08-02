<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Businesses\Models\Business;

/**
 * Punto único de consulta de límites de plan (4.1 del TODO): "mostrar
 * consumo y límites de forma anticipada". El consumo se cuenta al vuelo
 * (mismo criterio que las métricas — sin contadores precalculados que
 * puedan desincronizarse, ver docs/architecture/decisiones.md), no hay
 * una tabla `usage_limits` separada.
 */
class CheckUsageLimit
{
    /**
     * @return array{allowed: bool, used: int, limit: ?int}
     */
    public function handle(Business $business, string $limitKey): array
    {
        $limit = $business->activePlan()->limit($limitKey);
        $used = $this->currentUsage($business, $limitKey);

        return [
            'allowed' => $limit === null || $used < $limit,
            'used' => $used,
            'limit' => $limit,
        ];
    }

    private function currentUsage(Business $business, string $limitKey): int
    {
        return match ($limitKey) {
            'max_products' => $business->products()->count(),
            'max_members' => $business->members()->count(),
            default => 0,
        };
    }
}
