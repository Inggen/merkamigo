<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Actions\ApplyApprovedPayment;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\WompiWebhookEvent;
use App\Http\Controllers\Controller;
use App\Support\Wompi\WompiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook de eventos de Wompi (4.2 del TODO): notificación servidor a
 * servidor de cambios de estado de una transacción. Se procesa de forma
 * idempotente porque Wompi reintenta hasta 3 veces si no respondemos 2xx.
 */
class WompiWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $event = $request->json()->all();

        $wompi = app(WompiClient::class);

        if (! $wompi->verifyEventSignature($event)) {
            return response()->json(['message' => 'Firma inválida.'], 401);
        }

        if (($event['event'] ?? null) !== 'transaction.updated') {
            return response()->json(['message' => 'Evento ignorado.'], 200);
        }

        $checksum = $event['signature']['checksum'];

        if (WompiWebhookEvent::where('checksum', $checksum)->exists()) {
            return response()->json(['message' => 'Ya procesado.'], 200);
        }

        $transaction = $event['data']['transaction'] ?? [];

        WompiWebhookEvent::create([
            'wompi_transaction_id' => $transaction['id'] ?? '',
            'status' => $transaction['status'] ?? '',
            'checksum' => $checksum,
            'payload' => $event,
            'processed_at' => now(),
        ]);

        $payment = Payment::where('reference', $transaction['reference'] ?? '__none__')->first();

        if ($payment) {
            app(ApplyApprovedPayment::class)->handle(
                $payment,
                $transaction['status'] ?? 'ERROR',
                $transaction['id'] ?? null,
                $transaction,
            );
        }

        return response()->json(['message' => 'Procesado.'], 200);
    }
}
