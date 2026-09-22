<?php

namespace App\Http\Controllers\Marketplace;

use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Actions\ApplyApprovedOrder;
use App\Domain\Marketplace\Models\Order;
use App\Http\Controllers\Controller;
use App\Support\Wompi\BusinessWompiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook de Wompi DEL NEGOCIO (no de Merkamigo) — cada negocio pega esta
 * URL en el panel de SU propia cuenta Wompi. Verifica la firma con el
 * `events_secret` de ESE negocio, nunca el de Merkamigo. Mismo patrón de
 * idempotencia que `Billing\WompiWebhookController`: solo actúa sobre
 * `transaction.updated`, cualquier otro evento se ignora con 200.
 */
class BusinessWompiWebhookController extends Controller
{
    public function handle(Business $business, Request $request): JsonResponse
    {
        $credential = $business->wompiCredential;

        if (! $credential) {
            return response()->json(['message' => 'Negocio sin Wompi conectado.'], 404);
        }

        $event = $request->json()->all();
        $client = new BusinessWompiClient($credential);

        if (! $client->verifyEventSignature($event)) {
            return response()->json(['message' => 'Firma inválida.'], 401);
        }

        if (($event['event'] ?? null) !== 'transaction.updated') {
            return response()->json(['message' => 'Evento ignorado.']);
        }

        $transaction = $event['data']['transaction'] ?? null;

        if (! is_array($transaction)) {
            return response()->json(['message' => 'Evento ignorado.']);
        }

        $order = Order::where('reference', $transaction['reference'] ?? '__none__')
            ->where('business_id', $business->id)
            ->first();

        if ($order) {
            app(ApplyApprovedOrder::class)->handle(
                $order,
                $transaction['status'] ?? 'ERROR',
                $transaction['id'] ?? null,
                $transaction,
            );
        }

        return response()->json(['message' => 'Procesado.']);
    }
}
