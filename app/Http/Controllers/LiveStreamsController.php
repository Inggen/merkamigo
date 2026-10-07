<?php

namespace App\Http\Controllers;

use App\Domain\Social\Models\LiveStream;
use Illuminate\Contracts\View\View;

/**
 * Listado de transmisiones en vivo (antes "Eventos", navegación
 * simplificada del Cliente, 2026-10-03). Reubicado a `/en-vivo` el
 * 2026-10-05 (TODO_desarrollo_sistema_eventos_Merkamigo.md): "Eventos" en
 * el sidebar del Cliente ahora es la agenda pública nueva — decisión
 * confirmada por el usuario. Las transmisiones en vivo siguen
 * exactamente igual, solo sin su propio ítem de menú; se llega a ellas
 * desde la vitrina de cada negocio y desde `/en-vivo/{slug}` como antes.
 */
class LiveStreamsController extends Controller
{
    public function index(): View
    {
        $live = LiveStream::query()
            ->where('status', LiveStream::EN_VIVO)
            ->with(['business.storefront'])
            ->latest('started_at')
            ->get();

        $upcoming = LiveStream::query()
            ->where('status', LiveStream::BORRADOR)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->with(['business.storefront'])
            ->orderBy('scheduled_at')
            ->get();

        $replays = LiveStream::query()
            ->where('status', LiveStream::FINALIZADO)
            ->whereNotNull('replay_url')
            ->with(['business.storefront'])
            ->latest('ended_at')
            ->take(12)
            ->get();

        return view('en-vivo.index', [
            'live' => $live,
            'upcoming' => $upcoming,
            'replays' => $replays,
        ]);
    }
}
