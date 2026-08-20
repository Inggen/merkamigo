<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Actions\RefreshBusinessPaymentSourceStatus;
use App\Domain\Billing\Actions\RemoveBusinessPaymentSource;
use App\Domain\Billing\Actions\SaveBusinessPaymentSource;
use App\Domain\Billing\Models\WompiSetting;
use App\Domain\Businesses\Models\Business;
use App\Http\Controllers\Controller;
use App\Support\Wompi\WompiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Tarjeta guardada para la renovación mensual automática (4.2 del TODO).
 * El número de tarjeta y el CVV nunca pasan por aquí — el navegador los
 * tokeniza directamente contra la API de Wompi con la llave pública; este
 * controlador solo recibe el token resultante.
 */
class PaymentSourceController extends Controller
{
    /**
     * Llave pública y `acceptance_token`s que Wompi exige mostrar antes de
     * tokenizar una tarjeta (aceptación de términos y de tratamiento de
     * datos personales).
     */
    public function acceptanceTokens(Business $business): JsonResponse
    {
        $this->authorize('update', $business);

        $merchant = app(WompiClient::class)->fetchMerchant();

        if (! $merchant) {
            return response()->json(['message' => 'No pudimos conectar con Wompi en este momento.'], 503);
        }

        return response()->json([
            'public_key' => app(WompiClient::class)->publicKey(),
            'api_url' => WompiSetting::current()->apiUrl(),
            'acceptance_token' => $merchant['presigned_acceptance']['acceptance_token'] ?? null,
            'accept_personal_auth_token' => $merchant['presigned_personal_data_auth']['acceptance_token'] ?? null,
            'acceptance_permalink' => $merchant['presigned_acceptance']['permalink'] ?? null,
            'personal_auth_permalink' => $merchant['presigned_personal_data_auth']['permalink'] ?? null,
        ]);
    }

    public function store(Request $request, Business $business): JsonResponse
    {
        $this->authorize('update', $business);

        $validated = $request->validate([
            'card_token' => ['required', 'string'],
            'card_brand' => ['required', 'string', 'max:50'],
            'card_last_four' => ['required', 'string', 'size:4'],
            'customer_email' => ['required', 'email'],
            'acceptance_token' => ['required', 'string'],
            'accept_personal_auth_token' => ['required', 'string'],
        ]);

        try {
            $data = app(SaveBusinessPaymentSource::class)->handle(
                $business,
                $validated['card_token'],
                $validated['card_brand'],
                $validated['card_last_four'],
                $validated['customer_email'],
                $validated['acceptance_token'],
                $validated['accept_personal_auth_token'],
                $request->user(),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $data]);
    }

    public function status(Business $business): JsonResponse
    {
        $this->authorize('update', $business);

        try {
            $data = app(RefreshBusinessPaymentSourceStatus::class)->handle($business);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $data,
            'auto_renew_enabled' => $business->fresh()->auto_renew_enabled,
        ]);
    }

    public function destroy(Request $request, Business $business): JsonResponse
    {
        $this->authorize('update', $business);

        app(RemoveBusinessPaymentSource::class)->handle($business, $request->user());

        return response()->json(['message' => 'Tarjeta eliminada.']);
    }
}
