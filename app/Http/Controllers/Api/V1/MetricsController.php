<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Analytics\Actions\CalculateConversionFunnel;
use App\Domain\Analytics\Actions\CalculateProductPerformance;
use App\Domain\Analytics\Actions\CalculateReadableMetrics;
use App\Domain\Businesses\Models\Business;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Métricas del negocio (5.1/1.8/4.5 del TODO): agrupa en una sola
 * respuesta las mismas tres acciones que ya usa la página Livewire de
 * Métricas — sin duplicar el cálculo.
 */
class MetricsController extends Controller
{
    public function show(
        Request $request,
        Business $business,
        CalculateReadableMetrics $calculateReadableMetrics,
        CalculateConversionFunnel $calculateConversionFunnel,
        CalculateProductPerformance $calculateProductPerformance,
    ): JsonResponse {
        $this->authorize('view', $business);

        $productPerformance = collect($calculateProductPerformance->handle($business))
            ->map(fn (array $row) => [
                'product' => new ProductResource($row['product']),
                'views' => $row['views'],
                'whatsapp_clicks' => $row['whatsapp_clicks'],
                'last_viewed_at' => $row['last_viewed_at']?->toIso8601String(),
            ])
            ->values();

        return ApiResponse::response([
            'summary' => $calculateReadableMetrics->handle($business),
            'conversion_funnel' => $calculateConversionFunnel->handle($business),
            'product_performance' => $productPerformance,
        ]);
    }
}
