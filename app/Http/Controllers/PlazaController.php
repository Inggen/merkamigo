<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Immersive\Models\ImmersivePlaza;
use App\Domain\Needs\Models\Need;
use App\Domain\Storefronts\Models\Product;
use App\Support\Geo\Distance;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
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
    /**
     * Escena inmersiva genérica: arma el mundo caminable completo (suelo,
     * elementos, stands) a partir de los datos de la `ImmersivePlaza`
     * resuelta, sin ninguna geometría fija escrita a mano. Ver
     * `public/js/generic-plaza-immersive.js`.
     */
    public function genericPlaza(Municipality $municipio, Request $request): View
    {
        abort_unless($municipio->is_active, 404);

        $plaza = $this->resolvePrimaryPlaza($municipio, $request);

        abort_if($plaza === null, 404);

        return view('public.labs.generic-plaza', [
            'municipio' => $municipio,
            'plaza' => $plaza,
        ]);
    }

    /**
     * IMM-020b (puente mínimo de stands dinámicos): la primera plaza activa
     * de la experiencia del municipio, para que el JS de la escena sepa a
     * cuál plaza pedirle sus stands/elementos dinámicos. `null` si no hay
     * ninguna experiencia utilizable — las escenas con geometría fija
     * simplemente no dibujan nada encima; la escena genérica no tiene nada
     * que mostrar en absoluto (ver `genericPlaza()`).
     *
     * Normalmente exige que la experiencia esté publicada (nadie más debe
     * ver un borrador) Y que la plaza esté "activa". La excepción es
     * `?preview=1`: le permite a un administrador autenticado "entrar" a la
     * plaza tal cual quedaría antes de publicar — mismo mecanismo de
     * autorización que ya usan los recursos de Filament
     * (`hasAnyPlatformRole`), nunca público. Una plaza nueva empieza en
     * "borrador" (default de la columna) y normalmente se previsualiza
     * antes de activarla, así que en modo preview también se permite verla
     * en ese estado — de lo contrario `?preview=1` no serviría para su
     * caso de uso principal. Solo "archivada" queda excluida siempre (esa
     * sí es una plaza retirada a propósito, no algo en construcción).
     */
    private function resolvePrimaryPlaza(Municipality $municipio, Request $request): ?ImmersivePlaza
    {
        $isPreview = $this->canPreviewDraftExperience($request);

        $experience = $isPreview
            ? $municipio->immersiveExperiences()->latest()->first()
            : $municipio->publishedImmersiveExperience;

        return $experience?->plazas()
            ->when(
                $isPreview,
                fn ($query) => $query->where('status', '!=', 'archivada'),
                fn ($query) => $query->where('status', 'activa'),
            )
            ->orderBy('order')
            ->first();
    }

    private function canPreviewDraftExperience(Request $request): bool
    {
        return $request->boolean('preview')
            && (auth()->user()?->hasAnyPlatformRole(['admin', 'superadmin']) ?? false);
    }

    public function show(Municipality $municipio, Request $request): View
    {
        return view('plaza.show', $this->plazaData($municipio, null, $request));
    }

    public function category(Municipality $municipio, Category $categoria, Request $request): View
    {
        return view('plaza.show', $this->plazaData($municipio, $categoria, $request));
    }

    public function legacyBuscarRedirect(Request $request, ?string $municipio = null, ?string $categoria = null): RedirectResponse
    {
        $selectedMunicipality = $this->resolveSearchMunicipality($request, $municipio);
        $selectedCategory = $this->resolveSearchCategory($request, $categoria);

        return $this->buildCanonicalSearchRedirect($request, $selectedMunicipality, $selectedCategory, 301);
    }

    public function legacyCategoryRedirect(Request $request, Municipality $municipio, Category $categoria): RedirectResponse
    {
        return $this->buildCanonicalSearchRedirect($request, $municipio, $categoria, 301);
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
            ->servesMunicipality($municipio->id)
            ->where('status', 'publicado')
            ->when($categoria, fn ($q) => $q->where('category_id', $categoria->id))
            ->when($zone, fn ($q) => $q->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('municipality_id', $municipio->id)->where('zone', $zone))
                ->orWhereHas('municipalities', fn ($q) => $q
                    ->where('municipalities.id', $municipio->id)
                    ->where('business_municipalities.zone', $zone))));

        $featured = (clone $publishedBusinesses)
            ->where('featured_until', '>', now())
            ->with(['category', 'storefront'])
            ->orderByDesc('featured_until')
            ->take(4)
            ->get();

        $businessesQuery = (clone $publishedBusinesses)
            ->where(fn ($q) => $q->whereNull('featured_until')->orWhere('featured_until', '<=', now()))
            ->with(['category', 'storefront']);

        if ($near) {
            $businesses = $this->paginateByDistance($businessesQuery->get(), $near, $request);
        } else {
            $businesses = $businessesQuery->orderByDesc('created_at')->paginate(12, ['*'], 'page')->withQueryString();
        }

        $primaryZones = Business::query()
            ->where('municipality_id', $municipio->id)
            ->where('status', 'publicado')
            ->whereNotNull('zone')
            ->where('zone', '!=', '')
            ->distinct()
            ->pluck('zone');

        $additionalZones = Business::query()
            ->where('status', 'publicado')
            ->whereHas('municipalities', fn ($q) => $q->where('municipalities.id', $municipio->id))
            ->with(['municipalities' => fn ($q) => $q->where('municipalities.id', $municipio->id)])
            ->get()
            ->flatMap(fn (Business $business) => $business->municipalities->pluck('pivot.zone'))
            ->filter();

        $zones = $primaryZones->merge($additionalZones)->unique()->sort()->values();

        $openNeeds = Need::query()
            ->openIn($municipio->id, $categoria?->id)
            ->withCount('offers')
            ->with(['category'])
            ->latest('published_at')
            ->take(6)
            ->get();

        $products = Product::query()
            ->where('status', 'publicado')
            ->when($onlyAvailable, fn ($q) => $q->where('is_available', true))
            ->whereHas('business', fn ($q) => $q
                ->where(fn ($b) => $b
                    ->where('municipality_id', $municipio->id)
                    ->orWhereHas('municipalities', fn ($m) => $m->where('municipalities.id', $municipio->id)))
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
            'openNeeds' => $openNeeds,
            'products' => $products,
            'onlyAvailable' => $onlyAvailable,
            'near' => $near,
        ];
    }

    public function buscar(Request $request, ?string $municipio = null, ?string $categoria = null): View|RedirectResponse
    {
        $query = trim((string) $request->string('q'));
        $near = $this->nearMeCoordinates($request);
        $selectedMunicipality = $this->resolveSearchMunicipality($request, $municipio);
        $selectedCategory = $this->resolveSearchCategory($request, $categoria);
        $onlyAvailable = $request->boolean('disponibles');

        if ($redirect = $this->normalizeLegacySearchUrl($request, $selectedMunicipality, $selectedCategory, $municipio, $categoria)) {
            return $redirect;
        }

        if ($selectedMunicipality && $query === '' && ! $near) {
            return view('plaza.show', $this->plazaData($selectedMunicipality, $selectedCategory, $request));
        }

        $municipalityId = $selectedMunicipality?->id;
        $categoryId = $selectedCategory?->id;

        $businessesQuery = Business::query()
            ->where('status', 'publicado')
            ->when(
                $query !== '',
                fn (Builder $q) => $q->where(function (Builder $q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhereHas('products', fn (Builder $p) => $p
                            ->where('status', 'publicado')
                            ->where('name', 'like', "%{$query}%"));
                }),
            )
            ->when($municipalityId, fn (Builder $q) => $q->servesMunicipality($municipalityId))
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->with(['category', 'municipality', 'storefront']);

        if ($near) {
            $businesses = $this->paginateByDistance($businessesQuery->get(), $near, $request);
        } else {
            $businesses = $businessesQuery->orderByDesc('created_at')->paginate(12)->withQueryString();
        }

        $products = Product::query()
            ->where('status', 'publicado')
            ->when($onlyAvailable, fn (Builder $q) => $q->where('is_available', true))
            ->when(
                $query !== '',
                fn (Builder $q) => $q->where(function (Builder $q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhereHas('business', fn (Builder $b) => $b->where('name', 'like', "%{$query}%"));
                }),
            )
            ->whereHas('business', fn (Builder $b) => $b
                ->where('status', 'publicado')
                ->when($municipalityId, fn (Builder $b) => $b->where(fn (Builder $q) => $q
                    ->where('municipality_id', $municipalityId)
                    ->orWhereHas('municipalities', fn (Builder $m) => $m->where('municipalities.id', $municipalityId))))
                ->when($categoryId, fn (Builder $b) => $b->where('category_id', $categoryId)))
            ->with(['business', 'media'])
            ->orderByDesc('created_at')
            ->paginate(8, ['*'], 'productos_page')
            ->withQueryString();

        $openNeeds = Need::query()
            ->whereIn('status', [Need::PUBLICADA, Need::RECIBIENDO_OFERTAS])
            ->whereNull('suspended_at')
            ->when($municipalityId, fn (Builder $q) => $q->where('municipality_id', $municipalityId))
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->withCount('offers')
            ->with('category')
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('plaza.buscar', [
            'query' => $query,
            'municipalities' => Municipality::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('position')->get(),
            'selectedMunicipality' => $selectedMunicipality,
            'selectedCategory' => $selectedCategory,
            'businesses' => $businesses,
            'products' => $products,
            'onlyAvailable' => $onlyAvailable,
            'openNeeds' => $openNeeds,
            'near' => $near,
        ]);
    }

    private function normalizeLegacySearchUrl(
        Request $request,
        ?Municipality $municipality,
        ?Category $category,
        ?string $municipioSlug,
        ?string $categoriaSlug,
    ): ?RedirectResponse {
        $hasLegacyFilters = $request->filled('municipio') || $request->filled('categoria');

        if (! $hasLegacyFilters) {
            return null;
        }

        $targetUrl = $this->canonicalSearchUrl($request, $municipality, $category);
        $currentUrl = route('buscar', array_filter([
            'municipio' => $municipioSlug,
            'categoria' => $categoriaSlug,
        ], fn ($value) => filled($value)));
        $queryParameters = collect($request->query())
            ->except(['municipio', 'categoria'])
            ->reject(fn ($value) => $value === null || $value === '')
            ->all();
        $currentUrlWithQuery = $currentUrl.($queryParameters !== [] ? '?'.http_build_query($queryParameters) : '');

        if ($targetUrl === $currentUrlWithQuery) {
            return null;
        }

        return redirect()->to($targetUrl, 301);
    }

    private function buildCanonicalSearchRedirect(
        Request $request,
        ?Municipality $municipality,
        ?Category $category,
        int $status = 301,
    ): RedirectResponse {
        return redirect()->to($this->canonicalSearchUrl($request, $municipality, $category), $status);
    }

    private function canonicalSearchUrl(Request $request, ?Municipality $municipality, ?Category $category): string
    {
        $routeParameters = [];

        if ($municipality) {
            $routeParameters['municipio'] = $municipality->slug;
        } elseif ($category) {
            $routeParameters['municipio'] = 'todos';
        }

        if ($category) {
            $routeParameters['categoria'] = $category->slug;
        }

        $queryParameters = collect($request->query())
            ->except(['municipio', 'categoria'])
            ->reject(fn ($value) => $value === null || $value === '')
            ->all();

        return route('buscar', array_merge($routeParameters, $queryParameters));
    }

    private function resolveSearchMunicipality(Request $request, ?string $municipio): ?Municipality
    {
        $legacyMunicipalityId = $request->integer('municipio') ?: null;

        if ($legacyMunicipalityId) {
            return Municipality::query()
                ->where('is_active', true)
                ->find($legacyMunicipalityId);
        }

        if (! filled($municipio) || $municipio === 'todos') {
            return null;
        }

        return Municipality::query()
            ->where('is_active', true)
            ->where('slug', $municipio)
            ->first();
    }

    private function resolveSearchCategory(Request $request, ?string $categoria): ?Category
    {
        $legacyCategoryId = $request->integer('categoria') ?: null;

        if ($legacyCategoryId) {
            return Category::query()
                ->where('is_active', true)
                ->find($legacyCategoryId);
        }

        if (! filled($categoria)) {
            return null;
        }

        return Category::query()
            ->where('is_active', true)
            ->where('slug', $categoria)
            ->first();
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
