<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Plaza de mi municipio y buscador (1.5 del TODO). "Ofertas locales" y
 * "recomendados" quedan fuera de este pase — ofertas locales necesita más
 * volumen real de promociones, y recomendados depende de datos de la
 * Fase 3 (ver docs/architecture/decisiones.md). Destacados y nuevos ya
 * están cubiertos (Módulo B).
 */
class PlazaController extends Controller
{
    public function show(Municipality $municipio, Request $request): View
    {
        return view('plaza.show', $this->plazaData($municipio, null, $request));
    }

    public function category(Municipality $municipio, Category $categoria, Request $request): View
    {
        return view('plaza.show', $this->plazaData($municipio, $categoria, $request));
    }

    /**
     * @return array<string, mixed>
     */
    private function plazaData(Municipality $municipio, ?Category $categoria, Request $request): array
    {
        $zone = $request->string('zona')->value() ?: null;
        $onlyAvailable = $request->boolean('disponibles');

        $publishedBusinesses = Business::query()
            ->where('municipality_id', $municipio->id)
            ->where('status', 'publicado')
            ->when($categoria, fn ($q) => $q->where('category_id', $categoria->id))
            ->when($zone, fn ($q) => $q->where('zone', $zone));

        $featured = (clone $publishedBusinesses)
            ->where('featured_until', '>', now())
            ->with(['category', 'storefront'])
            ->orderByDesc('featured_until')
            ->take(6)
            ->get();

        $businesses = (clone $publishedBusinesses)
            ->where(fn ($q) => $q->whereNull('featured_until')->orWhere('featured_until', '<=', now()))
            ->with(['category', 'storefront'])
            ->orderByDesc('created_at')
            ->paginate(12, ['*'], 'page')
            ->withQueryString();

        $zones = Business::query()
            ->where('municipality_id', $municipio->id)
            ->where('status', 'publicado')
            ->whereNotNull('zone')
            ->where('zone', '!=', '')
            ->distinct()
            ->orderBy('zone')
            ->pluck('zone');

        $products = Product::query()
            ->where('status', 'publicado')
            ->when($onlyAvailable, fn ($q) => $q->where('is_available', true))
            ->whereHas('business', fn ($q) => $q
                ->where('municipality_id', $municipio->id)
                ->where('status', 'publicado')
                ->when($categoria, fn ($b) => $b->where('category_id', $categoria->id)))
            ->with(['business', 'media'])
            ->orderByDesc('created_at')
            ->paginate(9, ['*'], 'productos_page')
            ->withQueryString();

        return [
            'municipio' => $municipio,
            'municipalities' => Municipality::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('position')->get(),
            'category' => $categoria,
            'zones' => $zones,
            'zone' => $zone,
            'featured' => $featured,
            'businesses' => $businesses,
            'products' => $products,
            'onlyAvailable' => $onlyAvailable,
        ];
    }

    public function buscar(Request $request): View
    {
        $query = trim((string) $request->string('q'));
        $municipalityId = $request->integer('municipio') ?: null;
        $categoryId = $request->integer('categoria') ?: null;

        $businesses = Business::query()
            ->where('status', 'publicado')
            ->when(
                $query !== '',
                fn ($q) => $q->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhereHas('products', fn ($p) => $p
                            ->where('status', 'publicado')
                            ->where('name', 'like', "%{$query}%"));
                }),
            )
            ->when($municipalityId, fn ($q) => $q->where('municipality_id', $municipalityId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->with(['category', 'municipality', 'storefront'])
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('plaza.buscar', [
            'query' => $query,
            'municipalities' => Municipality::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('position')->get(),
            'businesses' => $businesses,
        ]);
    }

    public function municipios(): View
    {
        return view('public.municipios', [
            'municipalities' => Municipality::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function categorias(): View
    {
        return view('public.categorias', [
            'categories' => Category::where('is_active', true)->orderBy('position')->get(),
        ]);
    }
}
