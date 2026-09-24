<?php

namespace App\Http\Controllers\Marketplace;

use App\Domain\Marketplace\Actions\ApplyApprovedOrder;
use App\Domain\Marketplace\Actions\CreateLiveOrderCheckout;
use App\Domain\Marketplace\Actions\CreateOrderCheckout;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Social\Models\ContentPromotion;
use App\Domain\Social\Models\LiveStream;
use App\Domain\Storefronts\Models\Product;
use App\Http\Controllers\Controller;
use App\Support\Wompi\BusinessWompiClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Checkout de un pedido (Marketplace): mismo patrón que
 * `Billing\CheckoutController` (redirección hospedada, verificación
 * server-side al volver) pero firmado con la llave del NEGOCIO — el
 * dinero va directo a su cuenta Wompi, nunca a la de Merkamigo.
 */
class OrderCheckoutController extends Controller
{
    public function create(Product $product, Request $request): View|RedirectResponse|JsonResponse
    {
        $quantity = max(1, (int) $request->integer('cantidad', 1));
        $promotion = $request->filled('promotion')
            ? ContentPromotion::query()
                ->where('business_id', $product->business_id)
                ->where('status', ContentPromotion::ACTIVA)
                ->find($request->integer('promotion'))
            : null;

        try {
            $order = app(CreateOrderCheckout::class)->handle($product, $quantity, $request->user(), $promotion);
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['checkout' => $e->getMessage()]);
        }

        $credential = $order->business->wompiCredential;
        $client = new BusinessWompiClient($credential);

        // Pedido del usuario: el pago debe abrirse en una modal sin salir de
        // la página, no redirigir a la página hospedada de Wompi. El widget
        // de Wompi (resources/js/wompi-checkout.js) pide estos mismos datos
        // vía fetch con Accept: application/json; si el navegador no manda
        // ese header (JS deshabilitado, o alguien navega directo a la URL),
        // se sigue sirviendo la vista de respaldo con redirect al checkout
        // hospedado — el flujo de antes, que sigue funcionando igual.
        if ($request->wantsJson()) {
            return response()->json([
                'publicKey' => $client->publicKey(),
                'currency' => $order->currency,
                'amountInCents' => $order->amount_cents,
                'reference' => $order->reference,
                'signature' => app(CreateOrderCheckout::class)->integritySignature($order),
                'redirectUrl' => route('marketplace.checkout.return', $order),
            ]);
        }

        return view('marketplace.checkout', [
            'order' => $order,
            'business' => $order->business,
            'publicKey' => $client->publicKey(),
            'checkoutUrl' => $client->checkoutUrl(),
            'signature' => app(CreateOrderCheckout::class)->integritySignature($order),
            'redirectUrl' => route('marketplace.checkout.return', $order),
        ]);
    }

    public function return(Order $order, Request $request): View
    {
        $this->authorize('view', $order);

        $transactionId = $request->string('id')->value();

        if (! $transactionId || ! $order->business->wompiCredential) {
            return view('marketplace.checkout-return', ['order' => $order->fresh()]);
        }

        $client = new BusinessWompiClient($order->business->wompiCredential);
        $transaction = $client->fetchTransaction($transactionId);

        if ($transaction && ($transaction['reference'] ?? null) === $order->reference) {
            $order = app(ApplyApprovedOrder::class)->handle(
                $order,
                $transaction['status'] ?? 'ERROR',
                $transaction['id'] ?? $transactionId,
                $transaction,
            );
        }

        return view('marketplace.checkout-return', ['order' => $order->fresh()]);
    }

    public function createForLive(LiveStream $liveStream, Request $request): View|RedirectResponse|JsonResponse
    {
        abort_if($liveStream->status === LiveStream::BORRADOR, 404);

        try {
            $order = app(CreateLiveOrderCheckout::class)->handle(
                $liveStream,
                $request->session()->get("live_cart.{$liveStream->id}", []),
                $request->user(),
            );
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['checkout' => $e->getMessage()]);
        }

        $request->session()->forget("live_cart.{$liveStream->id}");
        $credential = $order->business->wompiCredential;
        $client = new BusinessWompiClient($credential);

        // Mismo patrón que `create()`: el widget de Wompi pide estos datos
        // por fetch (Accept: application/json) para abrir el pago en una
        // modal sobre el Live, sin salir de la página. El <form> real
        // sigue como respaldo (abre pestaña nueva al checkout hospedado)
        // si el fetch falla o JS está deshabilitado.
        if ($request->wantsJson()) {
            return response()->json([
                'publicKey' => $client->publicKey(),
                'currency' => $order->currency,
                'amountInCents' => $order->amount_cents,
                'reference' => $order->reference,
                'signature' => app(CreateOrderCheckout::class)->integritySignature($order),
                'redirectUrl' => route('marketplace.checkout.return', $order),
            ]);
        }

        return view('marketplace.checkout', [
            'order' => $order,
            'business' => $order->business,
            'publicKey' => $client->publicKey(),
            'checkoutUrl' => $client->checkoutUrl(),
            'signature' => app(CreateOrderCheckout::class)->integritySignature($order),
            'redirectUrl' => route('marketplace.checkout.return', $order),
        ]);
    }
}
