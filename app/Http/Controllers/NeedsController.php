<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Models\Offer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Pídelo en Merkamigo" (Fase 2 del TODO): páginas de exploración del
 * Cliente. La creación/edición vive en el componente Livewire
 * `pages::pidelo.nueva`; el detalle de una solicitud propia vive en
 * `pages::mis-solicitudes.show` — ambos necesitan interacción (guardado
 * automático, comparar y cerrar propuestas) que un controlador+Blade
 * sencillo no ofrece bien, igual que el resto del proyecto reserva
 * Livewire para formularios/paneles interactivos y Blade+Controller para
 * listados públicos (Plaza, vitrina).
 */
class NeedsController extends Controller
{
    public function index(Request $request): View
    {
        $municipality = $this->preferredMunicipality($request);

        $needs = Need::query()
            ->when($municipality, fn ($query) => $query->openIn($municipality->id))
            ->when(! $municipality, fn ($query) => $query->whereRaw('1 = 0'))
            ->withCount('offers')
            ->with(['category', 'user'])
            ->latest('published_at')
            ->paginate(10);

        return view('public.pidelo', [
            'municipality' => $municipality,
            'municipalities' => Municipality::where('is_active', true)->orderBy('name')->get(),
            'needs' => $needs,
        ]);
    }

    /**
     * Detalle público de una solicitud (2.2 del TODO): a diferencia de
     * `mis-solicitudes.show` (privada, solo el dueño puede comparar y
     * cerrar propuestas), cualquiera puede ver una solicitud ya publicada
     * — sin acciones, solo para saber qué se necesita. No depende del
     * municipio "preferido" de nadie: se accede por id, sin filtrar.
     */
    public function show(Need $need): View
    {
        abort_unless($need->isPublished(), 404);

        $need->loadMissing(['category', 'municipality', 'media', 'user']);
        $need->loadCount('offers');

        return view('public.pidelo-show', ['need' => $need]);
    }

    public function misSolicitudes(Request $request): View
    {
        $needs = $request->user()
            ->needs()
            ->withCount('offers')
            ->with(['category', 'municipality'])
            ->get();

        return view('public.mis-solicitudes', ['needs' => $needs]);
    }

    /**
     * "Continuar por WhatsApp" desde una propuesta (2.2 del TODO): registra
     * el contacto iniciado (reutilizando `analytics_events`, igual que
     * `VitrinaController::whatsapp`) y redirige, sin leer ni almacenar el
     * contenido de la conversación.
     */
    public function whatsapp(Need $need, Offer $offer, Request $request): RedirectResponse
    {
        abort_unless($offer->need_id === $need->id, 404);
        abort_if($request->user()->id !== $need->user_id, 403);
        abort_if(blank($offer->business->whatsapp_number), 404);

        app(RegisterAnalyticsEvent::class)->handle($offer->business, AnalyticsEvent::WHATSAPP_CLICK, $offer, $request);

        $text = __('Hola :negocio, te escribo por tu propuesta en Merkamigo para ":necesidad".', [
            'negocio' => $offer->business->name,
            'necesidad' => $need->title,
        ]);

        $number = preg_replace('/\D/', '', $offer->business->whatsapp_number);

        return redirect()->away("https://wa.me/{$number}?text=".urlencode($text));
    }

    /**
     * El municipio se toma primero de la URL (`?municipio=slug`, así el
     * enlace "Ver todas" de la Plaza de un municipio siempre apunta a ese
     * mismo municipio) y solo cae a la cookie de preferencia guardada
     * cuando no se pidió uno explícito — antes solo miraba la cookie, así
     * que venir desde la Plaza de un municipio podía terminar mostrando
     * las solicitudes de un municipio completamente distinto.
     */
    private function preferredMunicipality(Request $request): ?Municipality
    {
        $slug = $request->query('municipio') ?: $request->cookie('municipio');

        if (! $slug) {
            return null;
        }

        return Municipality::where('slug', $slug)->where('is_active', true)->first();
    }
}
