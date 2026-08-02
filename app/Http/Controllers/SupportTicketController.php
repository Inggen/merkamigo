<?php

namespace App\Http\Controllers;

use App\Domain\Moderation\Actions\SubmitSupportTicket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Solicitud de soporte por escrito (1.9 del TODO), alternativa al enlace
 * directo de WhatsApp en `/soporte` para quien prefiera dejar constancia.
 */
class SupportTicketController extends Controller
{
    public function create(): View
    {
        return view('soporte.solicitud');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'contact_email' => [$request->user() ? 'nullable' : 'required', 'email', 'max:255'],
        ]);

        app(SubmitSupportTicket::class)->handle(
            $data['subject'],
            $data['message'],
            $request->user(),
            $data['contact_email'] ?? null,
        );

        return redirect()->route('soporte')->with('status', __('Gracias, recibimos tu solicitud y te responderemos pronto.'));
    }
}
