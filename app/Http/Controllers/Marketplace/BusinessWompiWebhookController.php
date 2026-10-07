<?php

namespace App\Http\Controllers\Marketplace;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Actions\ApplyApprovedEventAttendancePayment;
use App\Domain\Events\Actions\ApplyApprovedEventPayment;
use App\Domain\Events\Models\EventAttendancePaymentAttempt;
use App\Domain\Events\Models\EventPaymentAttempt;
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

        $reference = $transaction['reference'] ?? '__none__';

        $order = Order::where('reference', $reference)
            ->where('business_id', $business->id)
            ->first();

        if ($order) {
            app(ApplyApprovedOrder::class)->handle(
                $order,
                $transaction['status'] ?? 'ERROR',
                $transaction['id'] ?? null,
                $transaction,
            );

            return response()->json(['message' => 'Procesado.']);
        }

        // Mismo webhook por negocio sirve a todo lo que se pague con su
        // Wompi propio — Marketplace (arriba) y reservas de eventos
        // (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 4) comparten
        // una sola URL registrada en el panel de Wompi del negocio.
        $attempt = EventPaymentAttempt::where('reference', $reference)
            ->whereHas('reservation', fn ($q) => $q->where('business_id', $business->id))
            ->first();

        if ($attempt) {
            app(ApplyApprovedEventPayment::class)->handle(
                $attempt,
                $transaction['status'] ?? 'ERROR',
                $transaction['id'] ?? null,
                $transaction,
            );

            return response()->json(['message' => 'Procesado.']);
        }

        // Reservas de CUPO/asistencia a eventos públicos (pedido del
        // usuario, 2026-10-08) — mismo webhook compartido.
        $attendanceAttempt = EventAttendancePaymentAttempt::where('reference', $reference)
            ->whereHas('attendance', fn ($q) => $q->where('business_id', $business->id))
            ->first();

        if ($attendanceAttempt) {
            app(ApplyApprovedEventAttendancePayment::class)->handle(
                $attendanceAttempt,
                $transaction['status'] ?? 'ERROR',
                $transaction['id'] ?? null,
                $transaction,
            );
        }

        return response()->json(['message' => 'Procesado.']);
    }
}
