<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Actions\SetPreferredMunicipality;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
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

        if (! $municipality) {
            return view('clientes.home', [
                'municipality' => null,
                'municipalities' => Municipality::where('is_active', true)->orderBy('name')->get(),
                'autoDetectMunicipalities' => Municipality::where('is_active', true)
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug', 'latitude', 'longitude']),
                'categories' => collect(),
                'businesses' => collect(),
            ]);
        }

        return view('clientes.home', [
            'municipality' => $municipality,
            'municipalities' => Municipality::where('is_active', true)->orderBy('name')->get(),
            'autoDetectMunicipalities' => collect(),
            'categories' => Category::where('is_active', true)->orderBy('position')->get(),
            'businesses' => Business::query()
                ->where('municipality_id', $municipality->id)
                ->where('status', 'publicado')
                ->with(['category', 'storefront'])
                ->latest('created_at')
                ->take(6)
                ->get(),
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

    private function preferredMunicipality(Request $request): ?Municipality
    {
        $slug = $request->cookie('municipio');

        if (! $slug) {
            return null;
        }

        return Municipality::where('slug', $slug)->where('is_active', true)->first();
    }
}
