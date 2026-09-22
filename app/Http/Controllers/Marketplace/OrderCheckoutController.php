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
    public function create(Product $product, Request $request): View|RedirectResponse
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
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }

        $credential = $order->business->wompiCredential;
        $client = new BusinessWompiClient($credential);

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

    public function createForLive(LiveStream $liveStream, Request $request): View|RedirectResponse
    {
        abort_if($liveStream->status === LiveStream::BORRADOR, 404);

        try {
            $order = app(CreateLiveOrderCheckout::class)->handle(
                $liveStream,
                $request->session()->get("live_cart.{$liveStream->id}", []),
                $request->user(),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }

        $request->session()->forget("live_cart.{$liveStream->id}");
        $credential = $order->business->wompiCredential;
        $client = new BusinessWompiClient($credential);

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
