<?php

namespace App\Domain\Analytics\Actions;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Trust\Models\OrderConfirmation;

/**
 * Embudo de conversión (4.5 del TODO): visita → clic a WhatsApp → pedido
 * completado, mismo periodo de 7 días que `CalculateReadableMetrics`. Los
 * pedidos completados no están necesariamente ligados 1:1 a un clic
 * particular (la conversación pasa por WhatsApp, fuera de Merkamigo), así
 * que el embudo es informativo — cuenta totales del periodo, no una
 * trazabilidad exacta clic→pedido.
 */
class CalculateConversionFunnel
{
    private const DAYS = 7;

    /**
     * @return array{
     *     visits: int,
     *     whatsapp_clicks: int,
     *     completed_orders: int,
     *     visit_to_click_rate: float|null,
     *     click_to_order_rate: float|null,
     * }
     */
    public function handle(Business $business): array
    {
        $periodStart = now()->subDays(self::DAYS - 1)->startOfDay();
        $periodEnd = now()->endOfDay();

        $visits = AnalyticsEvent::query()
            ->where('business_id', $business->id)
            ->whereIn('type', [AnalyticsEvent::VITRINA_VIEW, AnalyticsEvent::PRODUCTO_VIEW])
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $whatsappClicks = AnalyticsEvent::query()
            ->where('business_id', $business->id)
            ->where('type', AnalyticsEvent::WHATSAPP_CLICK)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $completedOrders = OrderConfirmation::query()
            ->where('business_id', $business->id)
            ->where('status', OrderConfirmation::COMPLETADO)
            ->whereBetween('completed_at', [$periodStart, $periodEnd])
            ->count();

        return [
            'visits' => $visits,
            'whatsapp_clicks' => $whatsappClicks,
            'completed_orders' => $completedOrders,
            'visit_to_click_rate' => $visits > 0 ? round(($whatsappClicks / $visits) * 100, 1) : null,
            'click_to_order_rate' => $whatsappClicks > 0 ? round(($completedOrders / $whatsappClicks) * 100, 1) : null,
        ];
    }
}
