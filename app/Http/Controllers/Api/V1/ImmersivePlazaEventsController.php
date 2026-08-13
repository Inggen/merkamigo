<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Analytics\Actions\RegisterImmersiveEvent;
use App\Domain\Analytics\Models\ImmersiveEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Storefronts\Models\Product;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * IMM-043: punto de entrada público para que las escenas inmersivas
 * registren telemetría de navegación (entrada a la plaza, búsqueda,
 * filtro de categoría, vitrina abierta, producto visto, clic a WhatsApp,
 * muestra de rendimiento). `product_id`/`business_id` se resuelven aquí en
 * vez de aceptar `subject_type` directo del cliente — exponer el nombre de
 * clase de Eloquent como contrato público sería frágil.
 */
class ImmersivePlazaEventsController extends Controller
{
    public function store(ImmersivePlaza $plaza, Request $request, RegisterImmersiveEvent $registerImmersiveEvent): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in([
                ImmersiveEvent::PLAZA_ENTRY,
                ImmersiveEvent::SEARCH_PERFORMED,
                ImmersiveEvent::CATEGORY_FILTERED,
                ImmersiveEvent::VITRINA_OPENED,
                ImmersiveEvent::PRODUCT_VIEWED,
                ImmersiveEvent::WHATSAPP_CLICK,
                ImmersiveEvent::PERFORMANCE_SAMPLE,
            ])],
            'business_id' => ['nullable', 'integer', 'exists:businesses,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'metadata' => ['nullable', 'array'],
            // Metadata nunca debe cargar texto libre sin límite (evita que
            // termine guardándose algo parecido a un dato personal por
            // accidente, ej. un query de búsqueda gigante).
            'metadata.*' => ['nullable', 'string', 'max:120'],
        ]);

        $business = filled($validated['business_id'] ?? null)
            ? Business::query()->whereKey($validated['business_id'])->first()
            : null;

        $product = filled($validated['product_id'] ?? null)
            ? Product::query()->whereKey($validated['product_id'])->first()
            : null;

        $registerImmersiveEvent->handle(
            plaza: $plaza,
            type: $validated['type'],
            request: $request,
            business: $business,
            subject: $product,
            metadata: $validated['metadata'] ?? null,
        );

        return ApiResponse::response(status: 202);
    }
}
