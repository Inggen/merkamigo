<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\MunicipalityResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\PublicBusinessResource;
use App\Support\Api\ApiResponse;
use App\Support\Geo\Distance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Descubrimiento público (5.1 del TODO): municipios, categorías, Plaza y
 * vitrinas publicadas, sin autenticación — el mismo contenido que ya es
 * público en `PlazaController`/`VitrinaController`, expuesto como JSON
 * para clientes externos.
 */
class DiscoveryController extends Controller
{
    public function municipios(): JsonResponse
    {
        // No se eager-carga `.municipality` sobre `publishedImmersiveExperience`
        // para mantener el grafo cacheado simple — `ImmersiveExperience::municipality()`
        // se resuelve con una query normal (barata, una sola fila) cuando
        // `labUrl()` la necesita.
        //
        // Se cachea el array YA RESUELTO por el resource (`->resolve()`),
        // nunca la `Collection` de modelos Eloquent cruda: cachear objetos
        // (con producción en Redis) dispara, de forma intermitente y real
        // en producción, "The script tried to call a method on an
        // incomplete object... Illuminate\Database\Eloquent\Collection...
        // unserialize()" — un problema conocido de PHP al deserializar
        // grafos de objetos con clases con autoload complejo. Un array
        // plano de escalares no tiene ese riesgo en absoluto.
        $municipalities = Cache::remember('api.v1.municipios', now()->addMinutes(10), fn () => MunicipalityResource::collection(
            Municipality::where('is_active', true)->with('publishedImmersiveExperience')->orderBy('name')->get()
        )->resolve());

        return ApiResponse::response($municipalities);
    }

    public function categorias(): JsonResponse
    {
        // Ver comentario de `municipios()`: mismo motivo para cachear el
        // array ya resuelto en vez de la `Collection` de modelos.
        $categories = Cache::remember('api.v1.categorias', now()->addMinutes(10), fn () => CategoryResource::collection(
            Category::where('is_active', true)->orderBy('position')->get()
        )->resolve());

        return ApiResponse::response($categories);
    }

    /**
     * Espeja los filtros de `PlazaController::buscar()` (municipio/
     * categoría por slug, texto libre, cercanía) — misma regla de negocio
     * (`Business::scopeServesMunicipality()`, `Distance::kilometers()`),
     * distinta capa de presentación (JSON en vez de Blade).
     */
    public function plaza(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));
        $municipality = filled($request->string('municipio')->value())
            ? Municipality::where('is_active', true)->where('slug', $request->string('municipio')->value())->first()
            : null;
        $category = filled($request->string('categoria')->value())
            ? Category::where('is_active', true)->where('slug', $request->string('categoria')->value())->first()
            : null;
        $near = $this->nearMeCoordinates($request);

        $businessesQuery = Business::query()
            ->where('status', 'publicado')
            ->when($query !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$query}%")
                ->orWhereHas('products', fn (Builder $p) => $p->where('status', 'publicado')->where('name', 'like', "%{$query}%"))))
            ->when($municipality, fn (Builder $q) => $q->servesMunicipality($municipality->id))
            ->when($category, fn (Builder $q) => $q->where('category_id', $category->id))
            ->with(['category', 'municipality', 'storefront', 'standAssignment.plaza.experience.municipality']);

        if ($near) {
            $businesses = $this->paginateByDistance($businessesQuery->get(), $near, $request);
        } else {
            $businesses = $businessesQuery->orderByDesc('created_at')->paginate(12)->withQueryString();
        }

        return ApiResponse::paginated($businesses, PublicBusinessResource::class);
    }

    public function business(Business $business): JsonResponse
    {
        abort_unless($business->isPublished(), 404);

        $business->load(['storefront', 'category', 'municipality', 'municipalities', 'verifications', 'standAssignment.plaza.experience.municipality']);

        return ApiResponse::response(new PublicBusinessResource($business));
    }

    public function products(Request $request, Business $business): JsonResponse
    {
        abort_unless($business->isPublished(), 404);

        $products = $business->products()
            ->where('status', 'publicado')
            ->with('media')
            ->orderBy('position')
            ->paginate(12)
            ->withQueryString();

        return ApiResponse::paginated($products, ProductResource::class);
    }

    public function product(Business $business, string $product): JsonResponse
    {
        abort_unless($business->isPublished(), 404);

        $product = $business->products()
            ->where('slug', $product)
            ->where('status', 'publicado')
            ->firstOrFail();

        return ApiResponse::response(new ProductResource($product->load(['media', 'variants'])));
    }

    /**
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
     * @param  Collection<int, Business>  $businesses
     * @param  array{lat: float, lng: float}  $near
     * @return LengthAwarePaginator<int, Business>
     */
    private function paginateByDistance(Collection $businesses, array $near, Request $request, int $perPage = 12): LengthAwarePaginator
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

        $page = max(1, (int) $request->input('page', 1));

        return new LengthAwarePaginator(
            $sorted->slice(($page - 1) * $perPage, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }
}
