<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Actions\SetPreferredMunicipality;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Models\Need;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Inicio de la experiencia Cliente (C01, 1.1.1 del TODO): municipio,
 * buscador, categorías y negocios destacados. "Destacados" hoy son los
 * publicados más recientes — todavía no existe una marca de destacado
 * administrable (ver docs/architecture/decisiones.md).
 */
class ClientesController extends Controller
{
    public function home(Request $request): View
    {
        $municipality = $this->preferredMunicipality($request);
        $municipalityId = $municipality?->id;

        // Sin municipio elegido, el Inicio no se queda vacío esperando una
        // elección: muestra negocios, productos y solicitudes de toda la
        // plataforma (sin filtrar), igual que `/plaza` — el municipio solo
        // acota estos mismos listados cuando el Cliente lo elige, nunca es
        // un requisito para ver contenido.
        $businesses = Business::query()
            ->where('status', 'publicado')
            ->when($municipalityId, fn ($query) => $query->where('municipality_id', $municipalityId))
            ->with(['category', 'storefront'])
            ->latest('created_at')
            ->take(6)
            ->get();

        $products = Product::query()
            ->where('status', 'publicado')
            ->whereHas('business', fn ($query) => $query
                ->where('status', 'publicado')
                ->when($municipalityId, fn ($query) => $query->where('municipality_id', $municipalityId)))
            ->with(['business', 'media'])
            ->latest('created_at')
            ->take(8)
            ->get();

        $openNeeds = Need::query()
            ->whereIn('status', [Need::PUBLICADA, Need::RECIBIENDO_OFERTAS])
            ->whereNull('suspended_at')
            ->when($municipalityId, fn ($query) => $query->where('municipality_id', $municipalityId))
            ->withCount('offers')
            ->with('category')
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('clientes.home', [
            'municipality' => $municipality,
            'municipalities' => Municipality::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('position')->get(),
            'businesses' => $businesses,
            'products' => $products,
            'openNeeds' => $openNeeds,
        ]);
    }

    public function favoritos(Request $request): View
    {
        $favorites = $request->user()->favorites()->with('favoritable')->latest()->get();

        return view('clientes.favoritos', [
            'businesses' => $favorites->pluck('favoritable')->filter(fn ($model) => $model instanceof Business),
            'products' => $favorites->pluck('favoritable')->filter(fn ($model) => $model instanceof Product),
        ]);
    }

    /**
     * Centro de actividad (0.2.2/1.1.1 del TODO): notificaciones de
     * propuestas recibidas o retiradas sobre las solicitudes del Cliente.
     */
    public function actividad(Request $request): View
    {
        return view('clientes.actividad', [
            'notifications' => $request->user()->notifications()->paginate(20),
            'recentlyViewed' => $request->user()->recentlyViewedBusinesses()->with('business')->take(8)->get(),
        ]);
    }

    public function setMunicipio(Request $request, SetPreferredMunicipality $setPreferredMunicipality): RedirectResponse
    {
        $data = $request->validate([
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
        ]);

        if (blank($data['municipality_id'] ?? null)) {
            Cookie::queue(Cookie::forget('municipio'));

            return redirect()->route('clientes.home');
        }

        $municipality = Municipality::where('id', $data['municipality_id'])->firstOrFail();

        $setPreferredMunicipality->handle($municipality);

        return redirect()->back(fallback: route('clientes.home'));
    }

    public function marcarActividadLeida(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return redirect()->route('clientes.actividad');
    }

    private function preferredMunicipality(Request $request): ?Municipality
    {
        $slug = $request->cookie('municipio');

        if (! $slug) {
            return null;
        }

        return Municipality::where('slug', $slug)->where('is_active', true)->first();
    }
}
