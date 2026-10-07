<?php

namespace App\Http\Controllers\Events;

use App\Domain\Events\Actions\ApplyApprovedEventAttendancePayment;
use App\Domain\Events\Actions\CreateEventAttendancePaymentAttempt;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventAttendance;
use App\Domain\Events\Models\EventAttendancePaymentAttempt;
use App\Http\Controllers\Controller;
use App\Support\Wompi\BusinessWompiClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Checkout de una reserva de CUPO a evento público: mismo patrón dual
 * que `EventReservationCheckoutController` (reservas de espacio), pago
 * directo a la cuenta Wompi del negocio.
 */
class EventAttendanceCheckoutController extends Controller
{
    public function create(EventAttendance $eventAttendance, Request $request): View|JsonResponse
    {
        try {
            $attempt = app(CreateEventAttendancePaymentAttempt::class)->handle($eventAttendance);
        } catch (EventActionException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            abort(422, $e->getMessage());
        }

        $credential = $eventAttendance->business->wompiCredential;
        $client = new BusinessWompiClient($credential);
        $signature = $client->integritySignature($attempt->reference, $attempt->amount_cents, $attempt->currency);
        $redirectUrl = route('eventos.entradas.retorno', $attempt);

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
            'reservation' => $eventAttendance,
            'business' => $eventAttendance->business,
            'publicKey' => $client->publicKey(),
            'checkoutUrl' => $client->checkoutUrl(),
            'attempt' => $attempt,
            'signature' => $signature,
            'redirectUrl' => $redirectUrl,
        ]);
    }

    public function return(EventAttendancePaymentAttempt $eventAttendancePaymentAttempt, Request $request): View
    {
        $transactionId = $request->string('id')->value();
        $attempt = $eventAttendancePaymentAttempt;
        $attendance = $attempt->attendance;

        $credential = $attendance->business->wompiCredential;

        if ($transactionId && $credential) {
            $client = new BusinessWompiClient($credential);
            $transaction = $client->fetchTransaction($transactionId);

            if ($transaction && ($transaction['reference'] ?? null) === $attempt->reference) {
                app(ApplyApprovedEventAttendancePayment::class)->handle(
                    $attempt,
                    $transaction['status'] ?? 'ERROR',
                    $transaction['id'] ?? $transactionId,
                    $transaction,
                );
            }
        }

        $attendance = $attendance->fresh();
        $ticketUrl = $attendance->status === EventAttendance::CONFIRMADA
            ? URL::signedRoute('eventos.entradas.show', ['eventAttendance' => $attendance->id])
            : null;

        return view('eventos.attendance-checkout-return', ['attendance' => $attendance, 'ticketUrl' => $ticketUrl]);
    }
}
