<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * `/dashboard` es el destino por defecto tras iniciar sesión (Fortify).
     * Si el usuario ya eligió experiencia, lo manda directo a su inicio; si
     * no, le muestra el selector (0.2 del TODO).
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        return match ($request->user()->experience) {
            'cliente' => redirect()->route('clientes.home'),
            'emprendedor' => redirect()->route('emprendedores.home'),
            default => view('dashboard'),
        };
    }
}
