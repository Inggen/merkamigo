<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ClientesController extends Controller
{
    /**
     * Inicio placeholder de la experiencia Cliente (C01). El contenido
     * completo (plaza, buscador, destacados) es de la Fase 1; aquí solo se
     * prueba la navegación y el layout diferenciados (0.2 del TODO).
     */
    public function home(): View
    {
        return view('clientes.home');
    }
}
