<?php

namespace App\Http\Controllers;

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
}
