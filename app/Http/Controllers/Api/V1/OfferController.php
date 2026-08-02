<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Businesses\Models\Business;
use App\Domain\Needs\Actions\SubmitOffer;
use App\Domain\Needs\Actions\WithdrawOffer;
use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Models\Offer;
use App\Http\Controllers\Controller;
use App\Http\Resources\OfferResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Propuestas de un negocio a una necesidad (5.1/2.2 del TODO). Reutiliza
 * `SubmitOffer`/`WithdrawOffer`, las mismas acciones que
 * `⚡oportunidades.blade.php`.
 */
class OfferController extends Controller
{
    public function store(Request $request, Business $business, Need $need, SubmitOffer $submitOffer): JsonResponse
    {
        $this->authorize('update', $business);

        $offer = $submitOffer->handle($business, $need, $request->only([
            'message', 'price', 'availability', 'product_id',
        ]), $request->user());

        return ApiResponse::response(new OfferResource($offer), status: 201);
    }

    public function index(Request $request, Business $business): JsonResponse
    {
        $this->authorize('update', $business);

        $offers = $business->offers()->paginate(12)->withQueryString();

        return ApiResponse::paginated($offers, OfferResource::class);
    }

    public function destroy(Request $request, Offer $offer, WithdrawOffer $withdrawOffer): JsonResponse
    {
        $this->authorize('withdraw', $offer);

        $offer = $withdrawOffer->handle($offer, $request->user());

        return ApiResponse::response(new OfferResource($offer));
    }
}
