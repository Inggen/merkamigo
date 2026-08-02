<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Enlace protegido al documento de verificación (3.1 del TODO: "cargar
 * documentos de forma privada y segura"). El middleware `business.team`
 * fija el equipo de la ruta, pero no autoriza por sí solo — se verifica
 * explícitamente aquí, igual que en cada componente Livewire del panel.
 */
class BusinessVerificationDocumentController extends Controller
{
    public function show(Business $business, Request $request): RedirectResponse
    {
        $this->authorize('view', $business);

        $verification = $business->currentVerification();

        abort_if(blank($verification?->verification_document_path), 404);

        return redirect()->away($verification->documentUrl());
    }
}
