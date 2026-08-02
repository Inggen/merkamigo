<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Businesses\Models\Business;
use App\Domain\Trust\Actions\RequestBusinessVerification;
use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessVerificationResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Solicitud de verificación de un negocio (5.1/3.1 del TODO). Multipart,
 * no JSON: `RequestBusinessVerification` recibe un `UploadedFile`, igual
 * que `⚡verificacion.blade.php`.
 */
class BusinessVerificationController extends Controller
{
    public function store(Request $request, Business $business, RequestBusinessVerification $requestBusinessVerification): JsonResponse
    {
        $this->authorize('update', $business);

        $data = $request->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_document_type' => ['required', 'string', 'max:50'],
            'contact_document_number' => ['required', 'string', 'max:50'],
            'request_note' => ['nullable', 'string', 'max:1000'],
            'document' => ['nullable', 'file', 'max:5120'],
        ]);

        $verification = $requestBusinessVerification->handle(
            $business,
            $request->user(),
            $data,
            $request->file('document'),
        );

        return ApiResponse::response(new BusinessVerificationResource($verification), status: 201);
    }
}
