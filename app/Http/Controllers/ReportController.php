<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Moderation\Actions\SubmitReport;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Trust\Models\Recommendation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Reportar contenido público (1.3/1.4 del TODO): un visitante o cliente
 * marca un negocio o producto como inapropiado, sin necesitar cuenta.
 */
class ReportController extends Controller
{
    private const REASONS = ['contenido_inapropiado', 'informacion_falsa', 'spam', 'otro'];

    public function createBusiness(Business $business): View
    {
        abort_unless($business->isPublished(), 404);

        return view('reportes.crear', [
            'title' => __('Reportar :name', ['name' => $business->name]),
            'actionUrl' => route('reportes.guardar.negocio', $business),
        ]);
    }

    public function storeBusiness(Request $request, Business $business): RedirectResponse
    {
        abort_unless($business->isPublished(), 404);

        $this->store($request, $business);

        return redirect()->route('vitrinas.show', $business)->with('status', __('Gracias, revisaremos tu reporte.'));
    }

    public function createProduct(Business $business, string $product): View
    {
        abort_unless($business->isPublished(), 404);

        $product = $business->products()->where('slug', $product)->where('status', 'publicado')->firstOrFail();

        return view('reportes.crear', [
            'title' => __('Reportar :name', ['name' => $product->name]),
            'actionUrl' => route('reportes.guardar.producto', [$business, $product]),
        ]);
    }

    public function storeProduct(Request $request, Business $business, string $product): RedirectResponse
    {
        abort_unless($business->isPublished(), 404);

        $product = $business->products()->where('slug', $product)->where('status', 'publicado')->firstOrFail();

        $this->store($request, $product);

        return redirect()->route('vitrinas.product', [$business, $product])->with('status', __('Gracias, revisaremos tu reporte.'));
    }

    public function createRecommendation(Business $business, int $recommendation): View
    {
        abort_unless($business->isPublished(), 404);

        $recommendation = $business->recommendations()
            ->where('status', Recommendation::PUBLICADA)
            ->findOrFail($recommendation);

        return view('reportes.crear', [
            'title' => __('Reportar recomendación'),
            'actionUrl' => route('reportes.guardar.recomendacion', [$business, $recommendation]),
        ]);
    }

    public function storeRecommendation(Request $request, Business $business, int $recommendation): RedirectResponse
    {
        abort_unless($business->isPublished(), 404);

        $recommendation = $business->recommendations()
            ->where('status', Recommendation::PUBLICADA)
            ->findOrFail($recommendation);

        $this->store($request, $recommendation);

        return redirect()->route('vitrinas.show', $business)->with('status', __('Gracias, revisaremos tu reporte.'));
    }

    private function store(Request $request, Business|Product|Recommendation $reportable): void
    {
        $data = $request->validate([
            'reason' => ['required', 'in:'.implode(',', self::REASONS)],
            'details' => ['nullable', 'string', 'max:1000'],
            'reporter_email' => ['nullable', 'email', 'max:255'],
        ]);

        app(SubmitReport::class)->handle(
            $reportable,
            $data['reason'],
            $data['details'] ?? null,
            $data['reporter_email'] ?? null,
        );
    }
}
