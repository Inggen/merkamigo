<?php

namespace App\Http\Controllers\Events;

use App\Domain\Events\Models\EventAttendance;
use App\Http\Controllers\Controller;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

/**
 * "Mi entrada": la página que ve el asistente con su QR de ingreso.
 * Alcanzable solo con el enlace firmado que llega por correo (Fase 7:
 * "proteger datos personales") — nadie puede verla adivinando el id.
 */
class EventAttendanceTicketController extends Controller
{
    public function show(EventAttendance $eventAttendance): View
    {
        $eventAttendance->load('publicEvent.business');

        // La página en sí ya llegó por un enlace firmado, pero el
        // middleware `signed` valida la firma de CADA petición por
        // separado — el <img> del QR necesita su propia URL firmada, no
        // hereda la de la página que lo contiene.
        $qrUrl = URL::signedRoute('eventos.entradas.qr', ['eventAttendance' => $eventAttendance->id]);

        return view('eventos.ticket', ['attendance' => $eventAttendance, 'qrUrl' => $qrUrl]);
    }

    public function qr(EventAttendance $eventAttendance): Response
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'outputBase64' => false,
            'imageTransparent' => false,
            'scale' => 8,
        ]);

        $png = (new QRCode($options))->render($eventAttendance->checkin_token_encrypted);

        return response($png, 200, ['Content-Type' => 'image/png']);
    }
}
