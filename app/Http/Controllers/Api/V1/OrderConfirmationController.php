<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Trust\Actions\ConfirmOrder;
use App\Domain\Trust\Actions\SubmitRecommendation;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderConfirmationResource;
use App\Http\Resources\RecommendationResource;
use App\Support\Api\ApiError;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Pedidos confirmados (5.1/3.2 del TODO), del lado del comprador o del
 * negocio — `OrderConfirmationPolicy` decide cuál de las dos partes es el
 * usuario autenticado. Reutiliza `ConfirmOrder`/`SubmitRecommendation`,
 * las mismas acciones que `⚡pedidos.blade.php` y
 * `⚡oportunidades.blade.php`.
 */
class OrderConfirmationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = OrderConfirmation::query()
            ->where('customer_user_id', $user->id)
            ->orWhereHas('business.members', fn ($q) => $q->where('users.id', $user->id))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return ApiResponse::paginated($orders, OrderConfirmationResource::class);
    }

    public function confirm(Request $request, OrderConfirmation $orderConfirmation, ConfirmOrder $confirmOrder): JsonResponse
    {
        $this->authorize('update', $orderConfirmation);

        $actor = $request->user();
        $order = $orderConfirmation->customer_user_id === $actor->id
            ? $confirmOrder->confirmAsCustomer($orderConfirmation, $actor)
            : $confirmOrder->confirmAsBusiness($orderConfirmation, $actor);

        return ApiResponse::response(new OrderConfirmationResource($order));
    }

    public function complete(Request $request, OrderConfirmation $orderConfirmation, ConfirmOrder $confirmOrder): JsonResponse
    {
        $this->authorize('update', $orderConfirmation);

        return ApiResponse::response(new OrderConfirmationResource($confirmOrder->complete($orderConfirmation, $request->user())));
    }

    public function dispute(Request $request, OrderConfirmation $orderConfirmation, ConfirmOrder $confirmOrder): JsonResponse
    {
        $this->authorize('update', $orderConfirmation);

        $note = $request->string('note')->value() ?: null;

        return ApiResponse::response(new OrderConfirmationResource($confirmOrder->markDisputed($orderConfirmation, $request->user(), $note)));
    }

    public function cancel(Request $request, OrderConfirmation $orderConfirmation, ConfirmOrder $confirmOrder): JsonResponse
    {
        $this->authorize('update', $orderConfirmation);

        $reason = $request->string('reason')->value() ?: null;

        return ApiResponse::response(new OrderConfirmationResource($confirmOrder->cancel($orderConfirmation, $request->user(), $reason)));
    }

    public function recommend(Request $request, OrderConfirmation $orderConfirmation, SubmitRecommendation $submitRecommendation): JsonResponse
    {
        $this->authorize('view', $orderConfirmation);

        try {
            $recommendation = $submitRecommendation->handle(
                $orderConfirmation,
                $request->user(),
                (string) $request->input('body'),
                (array) $request->input('tags', []),
            );
        } catch (InvalidArgumentException $e) {
            return ApiError::response($e->getMessage(), status: 422);
        }

        return ApiResponse::response(new RecommendationResource($recommendation), status: 201);
    }
}
