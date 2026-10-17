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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        if (! $request->user()) {
            // PR3 de TODO_VENTAS_RENTABILIDAD.md (P0.2): mismo
            // "degradación al login actual" que pide el TODO — si el
            // invitado no puede pagar este producto sin cuenta, se
            // manda a login conservando esta URL como `intended`
            // (`redirect()->guest()` es lo mismo que haría el
            // middleware `auth` si esta ruta siguiera detrás de él).
            if (! $this->guestCheckoutAvailable($product)) {
                return redirect()->guest(route('login'));
            }

            return view('marketplace.guest-checkout-form', [
                'product' => $product,
                'business' => $product->business,
                'quantity' => max(1, (int) $request->integer('cantidad', 1)),
            ]);
        }

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

    /**
     * PR3 de TODO_VENTAS_RENTABILIDAD.md (P0.2): paso previo de
     * `create()` cuando el visitante no tiene sesión — recibe sus datos
     * de contacto y crea el pedido como invitado. `CreateOrderCheckout`
     * vuelve a validar elegibilidad (flag + producto digital) por su
     * cuenta, así que el `abort_unless` de acá es solo para no exponer
     * el formulario de respaldo cuando ya se sabe que no aplica.
     */
    public function createGuest(Product $product, Request $request): View|RedirectResponse|JsonResponse
    {
        abort_unless($this->guestCheckoutAvailable($product), 404);

        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:30'],
            'cantidad' => ['nullable', 'integer', 'min:1'],
        ]);

        $quantity = max(1, (int) ($validated['cantidad'] ?? 1));

        try {
            $order = app(CreateOrderCheckout::class)->handle(
                $product,
                $quantity,
                null,
                null,
                null,
                $validated['guest_name'],
                $validated['guest_email'],
                $validated['guest_phone'],
            );
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['checkout' => $e->getMessage()])->withInput();
        }

        $credential = $order->business->wompiCredential;
        $client = new BusinessWompiClient($credential);
        // Único punto donde el retorno del widget cambia respecto a un
        // comprador con sesión: acá no hay nada que autorizar por
        // sesión, así que el enlace viene firmado — ver `guestReturn()`.
        $redirectUrl = URL::signedRoute('marketplace.guest.checkout.return', ['order' => $order->id]);

        if ($request->wantsJson()) {
            return response()->json([
                'publicKey' => $client->publicKey(),
                'currency' => $order->currency,
                'amountInCents' => $order->amount_cents,
                'reference' => $order->reference,
                'signature' => app(CreateOrderCheckout::class)->integritySignature($order),
                'redirectUrl' => $redirectUrl,
            ]);
        }

        return view('marketplace.checkout', [
            'order' => $order,
            'business' => $order->business,
            'publicKey' => $client->publicKey(),
            'checkoutUrl' => $client->checkoutUrl(),
            'signature' => app(CreateOrderCheckout::class)->integritySignature($order),
            'redirectUrl' => $redirectUrl,
        ]);
    }

    /**
     * Equivalente de `return()` para un invitado: no hay sesión que
     * autorizar, así que la propia URL firmada es la prueba de que
     * quien la abre es a quien Wompi le mostró el widget.
     */
    public function guestReturn(Order $order, Request $request): View
    {
        abort_unless($order->isGuestOrder(), 403);

        $transactionId = $request->string('id')->value();

        if ($transactionId && $order->business->wompiCredential) {
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
        }

        $order = $order->fresh();

        return view('marketplace.guest-confirmation', [
            'order' => $order,
            'downloadUrl' => $this->guestDownloadUrl($order),
        ]);
    }

    /**
     * La página a la que llega el invitado desde el enlace de su correo
     * de confirmación (`GuestOrderPaid`) — solo muestra el estado, no
     * vuelve a consultar Wompi (eso ya lo hizo `guestReturn()` o el
     * webhook).
     */
    public function guestConfirmation(Order $order): View
    {
        abort_unless($order->isGuestOrder(), 403);

        return view('marketplace.guest-confirmation', [
            'order' => $order,
            'downloadUrl' => $this->guestDownloadUrl($order),
        ]);
    }

    /**
     * Descarga del producto digital de un pedido de invitado — sin
     * `Entitlement` (no tiene cuenta a la que atarlo, ver
     * `ApplyApprovedOrder`), la prueba de acceso es la URL firmada
     * misma más que el pedido ya esté pagado.
     */
    public function guestDownload(Order $order): StreamedResponse
    {
        abort_unless($order->isGuestOrder(), 403);
        abort_unless($order->isPaid(), 403, 'Este pedido todavía no está pagado.');

        $product = $order->product;
        abort_unless($product && $product->isDigital(), 404);

        $file = $product->files()->firstOrFail();

        return Storage::disk('private')->download($file->path, $file->original_name);
    }

    private function guestCheckoutAvailable(Product $product): bool
    {
        return (bool) config('services.marketplace.guest_checkout_enabled') && $product->isDigital();
    }

    private function guestDownloadUrl(Order $order): ?string
    {
        if (! $order->isPaid() || ! $order->product?->isDigital()) {
            return null;
        }

        return URL::signedRoute('marketplace.guest.download', ['order' => $order->id]);
    }
}
