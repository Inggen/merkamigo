<?php

namespace App\Domain\WhatsApp\Actions;

use App\Domain\Analytics\Actions\CalculateProductPerformance;
use App\Domain\Businesses\Models\Business;

/**
 * Sugerencias del Copiloto de WhatsApp basadas en métricas reales (4.4 del
 * TODO) — nunca inventa datos, solo señala patrones que ya están en
 * `analytics_events` a través de `CalculateProductPerformance` (4.5). El
 * emprendedor decide si genera algo a partir de la sugerencia; nada se
 * envía ni se genera automáticamente.
 */
class SuggestWhatsAppContent
{
    private const STALE_DAYS = 14;

    /**
     * @return array<int, string>
     */
    public function handle(Business $business): array
    {
        $performance = app(CalculateProductPerformance::class)->handle($business);

        if ($performance === []) {
            return [];
        }

        $suggestions = [];

        foreach ($performance as $row) {
            $daysSinceViewed = $row['last_viewed_at']?->diffInDays(now());

            if ($daysSinceViewed !== null && $daysSinceViewed >= self::STALE_DAYS) {
                $suggestions[] = __('Tu producto :product no se ve hace :days días. Prueba generar una promoción para él.', [
                    'product' => $row['product']->name,
                    'days' => (int) $daysSinceViewed,
                ]);
            }

            if ($row['last_viewed_at'] === null) {
                $suggestions[] = __('Tu producto :product todavía no ha tenido vistas. Compártelo en tu estado de WhatsApp.', [
                    'product' => $row['product']->name,
                ]);
            }
        }

        return array_slice($suggestions, 0, 2);
    }
}
