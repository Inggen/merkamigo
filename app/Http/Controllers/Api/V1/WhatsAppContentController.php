<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use App\Domain\WhatsApp\Actions\GenerateWhatsAppPromotion;
use App\Domain\WhatsApp\Actions\SaveWhatsAppDraft;
use App\Domain\WhatsApp\Models\WhatsAppContent;
use App\Http\Controllers\Controller;
use App\Http\Resources\WhatsAppContentResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Copiloto de WhatsApp (5.1/1.7/4.4 del TODO). Reutiliza
 * `GenerateWhatsAppPromotion`/`SaveWhatsAppDraft`, las mismas acciones que
 * `⚡copiloto.blade.php` — nunca envía nada, solo genera y guarda texto.
 */
class WhatsAppContentController extends Controller
{
    public function index(Request $request, Business $business): JsonResponse
    {
        $this->authorize('view', $business);

        $history = WhatsAppContent::where('business_id', $business->id)->latest()->take(20)->get();

        return ApiResponse::response(WhatsAppContentResource::collection($history));
    }

    public function generate(Request $request, Business $business, GenerateWhatsAppPromotion $generateWhatsAppPromotion): JsonResponse
    {
        $this->authorize('update', $business);

        $data = $request->validate([
            'type' => ['required', 'in:promocion,estado,respuesta,presentacion'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'tone' => ['required', 'in:cercano,formal'],
            'length' => ['sometimes', 'in:corto,medio,largo'],
        ]);

        $product = ! empty($data['product_id']) ? Product::find((int) $data['product_id']) : null;

        $content = $generateWhatsAppPromotion->handle($business, $data['type'], $product, $data['tone'], $data['length'] ?? 'medio');

        return ApiResponse::response(['content' => $content]);
    }

    public function store(Request $request, Business $business, SaveWhatsAppDraft $saveWhatsAppDraft): JsonResponse
    {
        $this->authorize('update', $business);

        $data = $request->validate([
            'type' => ['required', 'in:promocion,estado,respuesta,presentacion'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'tone' => ['nullable', 'in:cercano,formal'],
            'content' => ['required', 'string'],
            'scheduled_for' => ['nullable', 'date'],
        ]);

        $product = ! empty($data['product_id']) ? Product::find((int) $data['product_id']) : null;

        $draft = $saveWhatsAppDraft->handle(
            $business,
            $data['type'],
            $product,
            $data['tone'] ?? null,
            $data['content'],
            $data['scheduled_for'] ?? null,
        );

        return ApiResponse::response(new WhatsAppContentResource($draft), status: 201);
    }
}
