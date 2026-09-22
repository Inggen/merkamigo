<?php

namespace App\Http\Controllers\Subscriptions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Subscriptions\Actions\CancelCustomerSubscription;
use App\Domain\Subscriptions\Actions\FetchCustomerPaymentSourceStatus;
use App\Domain\Subscriptions\Actions\SaveCustomerPaymentSource;
use App\Domain\Subscriptions\Actions\SubscribeToProduct;
use App\Domain\Subscriptions\Models\CustomerSubscription;
use App\Http\Controllers\Controller;
use App\Support\Wompi\BusinessWompiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Suscripción de un CLIENTE a un producto de un negocio (Fase 8.2 del
 * TODO social) — mismo patrón de tokenización que
 * `Billing\PaymentSourceController`, pero contra la cuenta Wompi DEL
 * NEGOCIO, nunca la de Merkamigo (ver `BusinessWompiClient`).
 */
class SubscriptionCheckoutController extends Controller
{
    public function acceptanceTokens(Business $business): JsonResponse
    {
        abort_unless($business->hasWompiConnected(), 422, 'Este negocio todavía no activó pagos en línea.');

        $client = new BusinessWompiClient($business->wompiCredential);
        $merchant = $client->fetchMerchant();

        if (! $merchant) {
            return response()->json(['message' => 'No pudimos conectar con Wompi en este momento.'], 503);
        }

        return response()->json([
            'public_key' => $client->publicKey(),
            'api_url' => $business->wompiCredential->apiUrl(),
            'acceptance_token' => $merchant['presigned_acceptance']['acceptance_token'] ?? null,
            'accept_personal_auth_token' => $merchant['presigned_personal_data_auth']['acceptance_token'] ?? null,
            'acceptance_permalink' => $merchant['presigned_acceptance']['permalink'] ?? null,
            'personal_auth_permalink' => $merchant['presigned_personal_data_auth']['permalink'] ?? null,
        ]);
    }

    public function savePaymentSource(Request $request, Business $business): JsonResponse
    {
        $validated = $request->validate([
            'card_token' => ['required', 'string'],
            'customer_email' => ['required', 'email'],
            'acceptance_token' => ['required', 'string'],
            'accept_personal_auth_token' => ['required', 'string'],
        ]);

        try {
            $data = app(SaveCustomerPaymentSource::class)->handle(
                $business,
                $validated['card_token'],
                $validated['customer_email'],
                $validated['acceptance_token'],
                $validated['accept_personal_auth_token'],
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $data]);
    }

    public function paymentSourceStatus(Business $business, string $paymentSourceId): JsonResponse
    {
        try {
            $data = app(FetchCustomerPaymentSourceStatus::class)->handle($business, $paymentSourceId);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $data]);
    }

    public function subscribe(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'wompi_payment_source_id' => ['required', 'string'],
            'card_brand' => ['required', 'string', 'max:50'],
            'card_last_four' => ['required', 'string', 'size:4'],
        ]);

        try {
            $subscription = app(SubscribeToProduct::class)->handle(
                $product,
                $validated['wompi_payment_source_id'],
                $validated['card_brand'],
                $validated['card_last_four'],
                $request->user(),
            );
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first()], 422);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => ['id' => $subscription->id, 'status' => $subscription->status]]);
    }

    public function cancel(Request $request, CustomerSubscription $subscription): JsonResponse
    {
        abort_unless($subscription->buyer_user_id === $request->user()->id, 403);

        app(CancelCustomerSubscription::class)->handle($subscription);

        return response()->json(['message' => 'Suscripción cancelada.']);
    }
}
