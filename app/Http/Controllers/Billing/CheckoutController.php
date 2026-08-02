<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Actions\ApplyApprovedPayment;
use App\Domain\Billing\Actions\CreatePaymentCheckout;
use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\Plan;
use App\Domain\Businesses\Models\Business;
use App\Http\Controllers\Controller;
use App\Support\Wompi\WompiClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Checkout de Wompi (4.2 del TODO): redirección hospedada por Wompi, con
 * la firma de integridad calculada en el servidor. Al volver, el estado
 * se verifica contra la API de Wompi — nunca se confía solo en la
 * redirección del navegador.
 */
class CheckoutController extends Controller
{
    public function createForPlan(Business $business, Plan $plan, Request $request): View|RedirectResponse
    {
        $this->authorize('update', $business);

        return $this->create($business, $plan, $request);
    }

    public function createForBillingProduct(Business $business, BillingProduct $billingProduct, Request $request): View|RedirectResponse
    {
        $this->authorize('update', $business);

        return $this->create($business, $billingProduct, $request);
    }

    private function create(Business $business, Plan|BillingProduct $item, Request $request): View|RedirectResponse
    {
        try {
            $payment = app(CreatePaymentCheckout::class)->handle($business, $item, $request->string('coupon')->value() ?: null, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['coupon' => $e->getMessage()]);
        }

        $wompi = app(WompiClient::class);

        return view('billing.checkout', [
            'payment' => $payment,
            'business' => $business,
            'publicKey' => $wompi->publicKey(),
            'checkoutUrl' => $wompi->checkoutUrl(),
            'signature' => app(CreatePaymentCheckout::class)->integritySignature($payment),
            'redirectUrl' => route('billing.checkout.return'),
        ]);
    }

    public function return(Request $request): View
    {
        $transactionId = $request->string('id')->value();

        $transaction = $transactionId ? app(WompiClient::class)->fetchTransaction($transactionId) : null;

        if (! $transaction) {
            return view('billing.checkout-return', ['payment' => null]);
        }

        $payment = Payment::where('reference', $transaction['reference'] ?? '__none__')->first();

        if ($payment) {
            $payment = app(ApplyApprovedPayment::class)->handle(
                $payment,
                $transaction['status'] ?? 'ERROR',
                $transaction['id'] ?? $transactionId,
                $transaction,
            );
        }

        return view('billing.checkout-return', ['payment' => $payment]);
    }
}
