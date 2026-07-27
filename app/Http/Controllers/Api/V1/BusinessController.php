<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessResource;
use App\Http\Resources\StorefrontResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    /**
     * Demuestra el aislamiento entre negocios (0.5 del TODO): solo un
     * miembro del negocio puede consultarlo; el middleware `business.team`
     * fija el team de spatie/permission y la policy verifica el rol.
     */
    public function show(Request $request, Business $business): JsonResponse
    {
        $this->authorize('view', $business);

        return ApiResponse::response(new BusinessResource($business->load('storefront')));
    }

    /**
     * Acción de ejemplo de 0.4: crea negocio + vitrina en borrador
     * reutilizando exactamente la misma acción de dominio que el
     * componente Livewire del panel del emprendedor y las pruebas Pest.
     */
    public function store(Request $request, CreateStorefront $createStorefront): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $storefront = $createStorefront->handle($request->user(), $data);

        return ApiResponse::response([
            'storefront' => new StorefrontResource($storefront),
            'business' => new BusinessResource($storefront->business),
        ], status: 201);
    }
}
