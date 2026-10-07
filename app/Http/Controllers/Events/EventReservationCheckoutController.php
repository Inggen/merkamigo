<?php

namespace App\Http\Controllers\Events;

use App\Domain\Events\Actions\ApplyApprovedEventPayment;
use App\Domain\Events\Actions\CreateEventPaymentAttempt;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventPaymentAttempt;
use App\Domain\Events\Models\EventReservation;
use App\Http\Controllers\Controller;
use App\Support\Wompi\BusinessWompiClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Checkout de una reserva de evento: mismo patrón dual que
 * `Marketplace\OrderCheckoutController` (JSON para el widget en modal,
 * vista de respaldo con redirect hospedado), firmado con la llave Wompi
 * DEL NEGOCIO. La reserva ya existe (la crea el cotizador antes de
 * llegar aquí) — este controlador solo abre/reabre el intento de pago.
 */
class EventReservationCheckoutController extends Controller
{
    public function create(EventReservation $eventReservation, Request $request): View|JsonResponse
    {
        try {
            $attempt = app(CreateEventPaymentAttempt::class)->handle($eventReservation);
        } catch (EventActionException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            abort(422, $e->getMessage());
        }

        $credential = $eventReservation->business->wompiCredential;
        $client = new BusinessWompiClient($credential);
        $signature = $client->integritySignature($attempt->reference, $attempt->amount_cents, $attempt->currency);
        $redirectUrl = route('eventos.reservas.retorno', $attempt);

        if ($request->wantsJson()) {
            return response()->json([
                'publicKey' => $client->publicKey(),
                'currency' => $attempt->currency,
                'amountInCents' => $attempt->amount_cents,
                'reference' => $attempt->reference,
                'signature' => $signature,
                'redirectUrl' => $redirectUrl,
            ]);
        }

        return view('eventos.checkout', [
            'reservation' => $eventReservation,
            'business' => $eventReservation->business,
            'publicKey' => $client->publicKey(),
            'checkoutUrl' => $client->checkoutUrl(),
            'attempt' => $attempt,
            'signature' => $signature,
            'redirectUrl' => $redirectUrl,
        ]);
    }

    public function return(EventPaymentAttempt $eventPaymentAttempt, Request $request): View
    {
        $transactionId = $request->string('id')->value();
        $attempt = $eventPaymentAttempt;
        $reservation = $attempt->reservation;

        $credential = $reservation->business->wompiCredential;

        if ($transactionId && $credential) {
            $client = new BusinessWompiClient($credential);
            $transaction = $client->fetchTransaction($transactionId);

            if ($transaction && ($transaction['reference'] ?? null) === $attempt->reference) {
                app(ApplyApprovedEventPayment::class)->handle(
                    $attempt,
                    $transaction['status'] ?? 'ERROR',
                    $transaction['id'] ?? $transactionId,
                    $transaction,
                );
            }
        }

        return view('eventos.checkout-return', ['reservation' => $reservation->fresh(), 'attempt' => $attempt->fresh()]);
    }
}
