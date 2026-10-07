<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Actions\CalculateReadableMetrics;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Events\Models\PublicEvent;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Storefronts\Actions\ResolveStorefrontQuota;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EmprendedoresController extends Controller
{
    /**
     * Inicio de la experiencia Emprendedor (E06, "panel de control" del
     * TODO): resumen del negocio, estado de publicación, guía de "qué te
     * falta para vender" y un vistazo rápido de métricas semanales por cada
     * negocio publicado.
     */
    public function home(
        Request $request,
        PublishStorefront $publishStorefront,
        CalculateReadableMetrics $calculateReadableMetrics,
        ResolveStorefrontQuota $resolveStorefrontQuota,
    ): View {
        $businesses = $request->user()->businesses()
            ->with(['storefront', 'municipality', 'category'])
            ->withCount('products')
            ->get();

        $missingByBusiness = $businesses
            ->reject(fn (Business $business) => $business->isPublished())
            ->mapWithKeys(fn (Business $business) => [$business->id => $publishStorefront->missingFieldsFor($business)]);

        $metricsByBusiness = $businesses
            ->filter(fn (Business $business) => $business->isPublished())
            ->mapWithKeys(fn (Business $business) => [$business->id => $calculateReadableMetrics->handle($business)]);

        $primaryBusiness = $businesses->first();
        $dashboard = null;
        $upcomingEvents = collect();
        $recentActivity = collect();

        if ($primaryBusiness) {
            $metrics = $metricsByBusiness[$primaryBusiness->id] ?? $calculateReadableMetrics->handle($primaryBusiness);
            $weekStart = now()->startOfWeek();

            $dashboard = [
                'views' => $metrics['total_views'],
                'views_change' => $this->percentageChange($metrics['total_views'], $metrics['previous_total_views']),
                'contacts' => $metrics['total_whatsapp_clicks'],
                'contacts_change' => $this->percentageChange($metrics['total_whatsapp_clicks'], $metrics['previous_total_whatsapp_clicks']),
                'orders' => $primaryBusiness->orders()->where('created_at', '>=', $weekStart)->count(),
                'redemptions' => LoyaltyRedemption::query()
                    ->whereHas('account', fn ($query) => $query->where('business_id', $primaryBusiness->id))
                    ->where('status', LoyaltyRedemption::ENTREGADO)
                    ->where('delivered_at', '>=', $weekStart)
                    ->count(),
            ];

            $upcomingEvents = $primaryBusiness->publicEvents()
                ->where('status', PublicEvent::PUBLICADO)
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')
                ->limit(3)
                ->get();

            $views = AnalyticsEvent::query()
                ->where('business_id', $primaryBusiness->id)
                ->whereIn('type', [AnalyticsEvent::VITRINA_VIEW, AnalyticsEvent::PRODUCTO_VIEW])
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn (AnalyticsEvent $event) => [
                    'type' => 'view',
                    'title' => __('Nueva visita en tu vitrina'),
                    'description' => $event->type === AnalyticsEvent::PRODUCTO_VIEW
                        ? __('Alguien vio uno de tus productos.')
                        : __('Alguien visitó tu página.'),
                    'occurred_at' => $event->created_at,
                ]);

            $posts = $primaryBusiness->posts()
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn ($post) => [
                    'type' => 'post',
                    'title' => __('Contenido actualizado'),
                    'description' => str($post->body)->squish()->limit(58)->toString() ?: __('Tu publicación ya está disponible.'),
                    'occurred_at' => $post->published_at ?? $post->created_at,
                ]);

            $orders = $primaryBusiness->orders()
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn (Order $order) => [
                    'type' => 'order',
                    'title' => __('Nuevo pedido'),
                    'description' => __('Pedido :reference · :status', [
                        'reference' => $order->reference,
                        'status' => ucfirst($order->status),
                    ]),
                    'occurred_at' => $order->created_at,
                ]);

            $recentActivity = $views
                ->concat($posts)
                ->concat($orders)
                ->sortByDesc('occurred_at')
                ->take(4)
                ->values();
        }

        return view('emprendedores.home', [
            'businesses' => $businesses,
            'primaryBusiness' => $primaryBusiness,
            'missingByBusiness' => $missingByBusiness,
            'metricsByBusiness' => $metricsByBusiness,
            'dashboard' => $dashboard,
            'upcomingEvents' => $upcomingEvents,
            'recentActivity' => $recentActivity,
            'storefrontQuota' => $resolveStorefrontQuota->handle($request->user()),
        ]);
    }

    private function percentageChange(int $current, int $previous): int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    /**
     * E01: bienvenida pública para quien todavía no tiene cuenta. Reutiliza
     * el mismo mecanismo de "municipio preferido" que ya usa el Cliente
     * (cookie `municipio`, ver `ClientesController::preferredMunicipality()`)
     * para mostrar una portada local cuando exista (1.6 del TODO).
     */
    public function bienvenida(Request $request): View
    {
        $slug = $request->cookie('municipio');

        $municipality = $slug
            ? Municipality::where('slug', $slug)->where('is_active', true)->first()
            : null;

        return view('emprendedores.bienvenida', ['municipality' => $municipality]);
    }

    /**
     * E03: vista previa asistida (también accesible tras publicar).
     */
    public function vistaPrevia(Business $business): View
    {
        $this->authorize('view', $business);

        $business->load(['storefront', 'municipality', 'category']);

        return view('emprendedores.negocios.vista-previa', ['business' => $business]);
    }

    /**
     * Compartir vitrina: enlace público y QR (1.3/1.6 del TODO).
     */
    public function compartir(Business $business): View
    {
        $this->authorize('view', $business);

        return view('emprendedores.negocios.compartir', ['business' => $business]);
    }
}
