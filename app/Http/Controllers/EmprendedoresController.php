<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EmprendedoresController extends Controller
{
    /**
     * Inicio placeholder de la experiencia Emprendedor (E06). El panel
     * completo (métricas, Copiloto de WhatsApp) es de la Fase 1; aquí se
     * prueba la navegación diferenciada y el estado vacío real cuando el
     * usuario todavía no tiene negocios (0.2 del TODO).
     */
    public function home(Request $request): View
    {
        $businesses = $request->user()->businesses;

        return view('emprendedores.home', ['businesses' => $businesses]);
    }

    /**
     * E01: bienvenida pública para quien todavía no tiene cuenta.
     */
    public function bienvenida(): View
    {
        return view('emprendedores.bienvenida');
    }

    /**
     * E03: vista previa asistida (también accesible tras publicar).
     */
    public function vistaPrevia(Business $business): View
    {
        $this->authorize('view', $business);

        $business->load(['storefront', 'municipality', 'category']);

        return view('emprendedores.negocios.vista-previa', ['business' => $business]);
    }

    /**
     * Compartir vitrina: enlace público y QR (1.3/1.6 del TODO).
     */
    public function compartir(Business $business): View
    {
        $this->authorize('view', $business);

        return view('emprendedores.negocios.compartir', ['business' => $business]);
    }
}
