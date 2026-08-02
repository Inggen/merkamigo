<?php

namespace App\Http\Controllers\Analytics;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Needs\Models\Offer;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportación de datos propios (4.5 del TODO): un CSV con los eventos,
 * propuestas y pedidos de este negocio — solo lo que le pertenece, nunca
 * datos de otros negocios ni de compradores fuera de lo que ya podía ver
 * en su propio panel.
 */
class MetricsExportController extends Controller
{
    public function export(Business $business): StreamedResponse
    {
        $this->authorize('view', $business);

        $filename = 'merkamigo-'.$business->slug.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($business) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                throw new \RuntimeException('No se pudo abrir el flujo de salida para la exportación.');
            }

            fputcsv($handle, ['tipo', 'fecha', 'detalle']);

            AnalyticsEvent::query()
                ->where('business_id', $business->id)
                ->orderBy('created_at')
                ->chunk(500, function ($events) use ($handle) {
                    foreach ($events as $event) {
                        fputcsv($handle, [
                            'evento',
                            $event->created_at->toDateTimeString(),
                            $event->type,
                        ]);
                    }
                });

            Offer::query()
                ->where('business_id', $business->id)
                ->with('need')
                ->orderBy('created_at')
                ->chunk(500, function ($offers) use ($handle) {
                    foreach ($offers as $offer) {
                        fputcsv($handle, [
                            'propuesta',
                            $offer->created_at->toDateTimeString(),
                            "estado={$offer->status}; necesidad=".($offer->need->title ?? '—'),
                        ]);
                    }
                });

            OrderConfirmation::query()
                ->where('business_id', $business->id)
                ->orderBy('created_at')
                ->chunk(500, function ($orders) use ($handle) {
                    foreach ($orders as $order) {
                        fputcsv($handle, [
                            'pedido',
                            $order->created_at->toDateTimeString(),
                            "estado={$order->status}",
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
