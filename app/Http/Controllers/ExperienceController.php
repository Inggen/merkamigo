<?php

namespace App\Http\Controllers;

use App\Domain\Identity\Actions\SwitchExperience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    public function update(Request $request, SwitchExperience $switchExperience): RedirectResponse
    {
        $data = $request->validate([
            'experience' => ['required', 'string', 'in:'.implode(',', SwitchExperience::OPTIONS)],
        ]);

        $switchExperience->handle($request->user(), $data['experience']);

        if (! $request->user()) {
            // Un invitado todavía no tiene dónde navegar en esa experiencia
            // (Explorar sin registro es Fase 1); la cookie ya quedó lista
            // para que, al registrarse, arranque en la experiencia elegida.
            return redirect()->route('register');
        }

        return redirect()->to(
            $data['experience'] === 'cliente' ? route('clientes.home') : route('emprendedores.home'),
        );
    }
}
