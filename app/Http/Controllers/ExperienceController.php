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
            return redirect()->to(
                $data['experience'] === 'cliente'
                    ? route('clientes.home')
                    : route('emprendedores.bienvenida'),
            );
        }

        return redirect()->to(
            $data['experience'] === 'cliente' ? route('clientes.home') : route('emprendedores.home'),
        );
    }
}
