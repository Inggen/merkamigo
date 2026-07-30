<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Models\Product;
use App\Support\Geo\Distance;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Plaza de mi municipio y buscador (1.5 del TODO). "Ofertas locales" y
 * "recomendados" quedan fuera de este pase — ofertas locales necesita más
 * volumen real de promociones, y recomendados depende de datos de la
 * Fase 3 (ver docs/architecture/decisiones.md). Destacados y nuevos ya
 * están cubiertos (Módulo B).
 *
 * "Cerca de mí" (1.1.1/1.5) es un orden, no un filtro excluyente: cuando
 * el visitante comparte su ubicación por una sola vez (sin guardarla en
 * el servidor), los negocios con coordenadas propias se ordenan por
 * distancia y el resto sigue apareciendo después, en su orden habitual —
 * así ningún negocio desaparece solo por no tener coordenadas todavía.
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
        $near = $this->nearMeCoordinates($request);

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

        $businessesQuery = (clone $publishedBusinesses)
            ->where(fn ($q) => $q->whereNull('featured_until')->orWhere('featured_until', '<=', now()))
            ->with(['category', 'storefront']);

        if ($near) {
            $businesses = $this->paginateByDistance($businessesQuery->get(), $near, $request);
        } else {
            $businesses = $businessesQuery->orderByDesc('created_at')->paginate(12, ['*'], 'page')->withQueryString();
        }

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
            'near' => $near,
        ];
    }

    public function buscar(Request $request): View
    {
        $query = trim((string) $request->string('q'));
        $municipalityId = $request->integer('municipio') ?: null;
        $categoryId = $request->integer('categoria') ?: null;
        $near = $this->nearMeCoordinates($request);

        $businessesQuery = Business::query()
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
            ->with(['category', 'municipality', 'storefront']);

        if ($near) {
            $businesses = $this->paginateByDistance($businessesQuery->get(), $near, $request);
        } else {
            $businesses = $businessesQuery->orderByDesc('created_at')->paginate(12)->withQueryString();
        }

        return view('plaza.buscar', [
            'query' => $query,
            'municipalities' => Municipality::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('position')->get(),
            'businesses' => $businesses,
            'near' => $near,
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

    public function categoriaPublica(Category $categoria): View
    {
        abort_unless($categoria->is_active, 404);

        $municipalities = Municipality::query()
            ->where('is_active', true)
            ->whereHas('businesses', fn ($query) => $query
                ->where('status', 'publicado')
                ->where('category_id', $categoria->id))
            ->withCount([
                'businesses as published_businesses_count' => fn ($query) => $query
                    ->where('status', 'publicado')
                    ->where('category_id', $categoria->id),
            ])
            ->orderByDesc('published_businesses_count')
            ->orderBy('name')
            ->get();

        $featuredBusinesses = Business::query()
            ->where('status', 'publicado')
            ->where('category_id', $categoria->id)
            ->with(['municipality', 'storefront', 'category'])
            ->orderByDesc('featured_until')
            ->orderByDesc('created_at')
            ->take(9)
            ->get();

        return view('public.categoria', [
            'category' => $categoria,
            'municipalities' => $municipalities,
            'featuredBusinesses' => $featuredBusinesses,
        ]);
    }

    /**
     * Coordenadas de "cerca de mí" enviadas por el control de la Plaza
     * (compartidas una sola vez desde el navegador, nunca persistidas).
     * Cualquier valor fuera de rango o incompleto se ignora en silencio:
     * la cercanía es una mejora opcional, nunca un requisito para ver la
     * Plaza.
     *
     * @return array{lat: float, lng: float}|null
     */
    private function nearMeCoordinates(Request $request): ?array
    {
        if (! $request->filled('lat') || ! $request->filled('lng')) {
            return null;
        }

        $lat = $request->float('lat');
        $lng = $request->float('lng');

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * Ordena una colección ya cargada de negocios por distancia y la
     * pagina manualmente. Se calcula en PHP (no en SQL) para no depender
     * del motor de base de datos — ver `App\Support\Geo\Distance` — así
     * que aquí se trabaja sobre una colección completa en vez de un
     * query paginado en la base de datos; al volumen del piloto (pocas
     * decenas de negocios por municipio) es más simple que forzar una
     * fórmula de distancia en SQL sin perder portabilidad entre MySQL y
     * SQLite (pruebas).
     *
     * @param  Collection<int, Business>  $businesses
     * @param  array{lat: float, lng: float}  $near
     * @return LengthAwarePaginator<int, Business>
     */
    private function paginateByDistance(Collection $businesses, array $near, Request $request, string $pageName = 'page', int $perPage = 12): LengthAwarePaginator
    {
        $sorted = $businesses
            ->each(function (Business $business) use ($near) {
                $business->distance_km = $business->hasCoordinates()
                    ? Distance::kilometers($near['lat'], $near['lng'], $business->latitude, $business->longitude)
                    : null;
            })
            ->sort(function (Business $a, Business $b) {
                if (($a->distance_km === null) !== ($b->distance_km === null)) {
                    return $a->distance_km === null ? 1 : -1;
                }

                if ($a->distance_km !== null) {
                    return $a->distance_km <=> $b->distance_km;
                }

                return $b->created_at <=> $a->created_at;
            })
            ->values();

        $page = max(1, (int) $request->input($pageName, 1));

        return new LengthAwarePaginator(
            $sorted->slice(($page - 1) * $perPage, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => $pageName],
        );
    }
}
