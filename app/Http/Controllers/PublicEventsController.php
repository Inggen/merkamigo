<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Events\Actions\RankPublicEventsForAgenda;
use App\Domain\Events\Models\PublicEvent;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Agenda pública de eventos (TODO_desarrollo_sistema_eventos_Merkamigo.md,
 * Fases 5/6): listado filtrable por municipio/fecha/categoría, ordenado
 * por fecha y, dentro de un mismo día, con impulso de visibilidad por
 * plan (`RankPublicEventsForAgenda`) — rotación y diversidad incluidas,
 * ningún negocio ni plan queda excluido de los resultados.
 */
class PublicEventsController extends Controller
{
    /**
     * Techo de eventos candidatos a ordenar en memoria antes de paginar.
     * Suficiente para esta fase (agenda todavía incipiente); si el
     * volumen real de eventos publicados lo supera, este filtrado pasará
     * a resolverse en BD con una columna de score precalculado en vez de
     * aumentar este número indefinidamente.
     */
    private const CANDIDATE_LIMIT = 300;

    public function index(Request $request): View
    {
        $municipalitySlug = $request->query('municipio');
        $category = $request->string('categoria')->value() ?: null;
        $date = $request->string('fecha')->value() ?: null;

        $municipality = $municipalitySlug
            ? Municipality::query()->where('slug', $municipalitySlug)->where('is_active', true)->first()
            : null;

        $candidates = PublicEvent::query()
            ->with(['business.subscription.plan', 'business.municipality', 'municipality'])
            ->where('status', PublicEvent::PUBLICADO)
            ->where('starts_at', '>=', now()->startOfDay())
            ->when($municipality, fn ($q) => $q->where('municipality_id', $municipality->id))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($date, fn ($q) => $q->whereDate('starts_at', $date))
            ->orderBy('starts_at')
            ->limit(self::CANDIDATE_LIMIT)
            ->get();

        $ranked = app(RankPublicEventsForAgenda::class)->handle($candidates);

        $page = max(1, (int) $request->integer('page', 1));
        $perPage = 12;
        $events = new LengthAwarePaginator(
            $ranked->slice(($page - 1) * $perPage, $perPage)->values(),
            $ranked->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        // Fase 6: "medir impresiones... por vitrina y plan" — solo de lo
        // que realmente se renderiza en esta página, no de los 300
        // candidatos evaluados para el orden.
        foreach ($events as $event) {
            app(RegisterAnalyticsEvent::class)->handle($event->business, AnalyticsEvent::EVENT_IMPRESSION, $event, $request);
        }

        $municipalities = Municipality::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'department']);

        $heroMunicipality = $municipality ?? $municipalities->firstWhere('slug', $request->cookie('municipio'));

        return view('eventos.index', [
            'events' => $events,
            'municipalities' => $municipalities,
            'selectedMunicipality' => $municipality,
            'categories' => PublicEvent::CATEGORIES,
            'selectedCategory' => $category,
            'selectedDate' => $date,
            'heroMunicipality' => $heroMunicipality,
        ]);
    }

    public function show(PublicEvent $publicEvent, Request $request): View
    {
        abort_unless(in_array($publicEvent->status, [PublicEvent::PUBLICADO, PublicEvent::FINALIZADO], true), 404);

        $publicEvent->load([
            'business.organization', 'business.municipality', 'business.verifications',
            'business.recommendations', 'business.eventSetting', 'municipality',
        ]);

        app(RegisterAnalyticsEvent::class)->handle($publicEvent->business, AnalyticsEvent::EVENT_DETAIL_VIEW, $publicEvent, $request);

        $related = PublicEvent::query()
            ->with('business')
            ->where('status', PublicEvent::PUBLICADO)
            ->where('id', '!=', $publicEvent->id)
            ->where('starts_at', '>=', now())
            ->when($publicEvent->municipality_id, fn ($q) => $q->where('municipality_id', $publicEvent->municipality_id))
            ->orderBy('starts_at')
            ->limit(2)
            ->get();

        return view('eventos.show', [
            'event' => $publicEvent,
            'related' => $related,
        ]);
    }
}
