<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Actions\CalculateReadableMetrics;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\PublishStorefront;
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
    public function home(Request $request, PublishStorefront $publishStorefront, CalculateReadableMetrics $calculateReadableMetrics): View
    {
        $businesses = $request->user()->businesses()->with('storefront')->get();

        $missingByBusiness = $businesses
            ->reject(fn (Business $business) => $business->isPublished())
            ->mapWithKeys(fn (Business $business) => [$business->id => $publishStorefront->missingFieldsFor($business)]);

        $metricsByBusiness = $businesses
            ->filter(fn (Business $business) => $business->isPublished())
            ->mapWithKeys(fn (Business $business) => [$business->id => $calculateReadableMetrics->handle($business)]);

        return view('emprendedores.home', [
            'businesses' => $businesses,
            'missingByBusiness' => $missingByBusiness,
            'metricsByBusiness' => $metricsByBusiness,
        ]);
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
