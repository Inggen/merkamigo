<?php

namespace App\Livewire;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use App\Support\Geo\Distance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CatalogResults extends Component
{
    use WithPagination;

    public string $type;

    public ?int $municipalityId = null;

    public ?int $categoryId = null;

    public string $query = '';

    public ?string $zone = null;

    public ?float $latitude = null;

    public ?float $longitude = null;

    /**
     * Radio de "Cerca de mí" en km (Fase 5.1 del TODO social) — a
     * diferencia del orden por cercanía (siempre activo con
     * latitude/longitude, nunca excluye), esto SÍ filtra: un negocio o
     * producto fuera del radio (o sin coordenadas) queda fuera de la
     * lista, no solo al final.
     */
    public ?float $radiusKm = null;

    public bool $onlyAvailable = false;

    public ?float $minPrice = null;

    public ?float $maxPrice = null;

    public bool $excludeFeatured = false;

    public int $perPage = 8;

    public function mount(): void
    {
        abort_unless(in_array($this->type, ['businesses', 'products'], true), 404);

        $this->perPage = min(24, max(1, $this->perPage));
    }

    public function render(): View
    {
        $results = $this->type === 'products'
            ? $this->products()
            : $this->businesses();

        return view('livewire.catalog-results', compact('results'));
    }

    private function businesses(): LengthAwarePaginator
    {
        $query = Business::query()
            ->where('status', 'publicado')
            ->when(
                $this->query !== '',
                fn (Builder $query) => $query->where(function (Builder $query) {
                    $query->where('name', 'like', "%{$this->query}%")
                        ->orWhereHas('products', fn (Builder $products) => $products
                            ->where('status', 'publicado')
                            ->where('name', 'like', "%{$this->query}%"));
                }),
            )
            ->when($this->municipalityId, fn (Builder $query) => $query->servesMunicipality($this->municipalityId))
            ->when($this->categoryId, fn (Builder $query) => $query->where('category_id', $this->categoryId))
            ->when($this->excludeFeatured, fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->whereNull('featured_until')
                    ->orWhere('featured_until', '<=', now())))
            ->when($this->zone && $this->municipalityId, fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where(fn (Builder $query) => $query
                        ->where('municipality_id', $this->municipalityId)
                        ->where('zone', $this->zone))
                    ->orWhereHas('municipalities', fn (Builder $municipalities) => $municipalities
                        ->where('municipalities.id', $this->municipalityId)
                        ->where('business_municipalities.zone', $this->zone))))
            ->with(['category', 'municipality', 'storefront']);

        if ($this->latitude === null || $this->longitude === null) {
            return $query
                ->orderByDesc('created_at')
                ->paginate($this->perPage, ['*'], $this->pageName());
        }

        $businesses = $query->get()
            ->each(function (Business $business) {
                $business->distance_km = $business->hasCoordinates()
                    ? Distance::kilometers($this->latitude, $this->longitude, $business->latitude, $business->longitude)
                    : null;
            })
            ->sort(function (Business $first, Business $second) {
                if (($first->distance_km === null) !== ($second->distance_km === null)) {
                    return $first->distance_km === null ? 1 : -1;
                }

                if ($first->distance_km !== null) {
                    return $first->distance_km <=> $second->distance_km;
                }

                return $second->created_at <=> $first->created_at;
            })
            ->values();

        if ($this->radiusKm !== null) {
            $businesses = $businesses->filter(fn (Business $business) => $business->distance_km !== null && $business->distance_km <= $this->radiusKm)->values();
        }

        $page = $this->getPage($this->pageName());

        return new LengthAwarePaginator(
            $businesses->forPage($page, $this->perPage)->values(),
            $businesses->count(),
            $this->perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $this->pageName(),
            ],
        );
    }

    private function products(): LengthAwarePaginator
    {
        $query = Product::query()
            ->where('status', 'publicado')
            ->when($this->onlyAvailable, fn (Builder $query) => $query->where('is_available', true))
            ->when($this->minPrice !== null, fn (Builder $query) => $query->where('price', '>=', $this->minPrice))
            ->when($this->maxPrice !== null, fn (Builder $query) => $query->where('price', '<=', $this->maxPrice))
            ->when(
                $this->query !== '',
                fn (Builder $query) => $query->where(function (Builder $query) {
                    $query->where('name', 'like', "%{$this->query}%")
                        ->orWhereHas('business', fn (Builder $businesses) => $businesses
                            ->where('name', 'like', "%{$this->query}%"));
                }),
            )
            ->whereHas('business', fn (Builder $businesses) => $businesses
                ->where('status', 'publicado')
                ->when($this->municipalityId, fn (Builder $businesses) => $businesses->servesMunicipality($this->municipalityId))
                ->when($this->categoryId, fn (Builder $businesses) => $businesses->where('category_id', $this->categoryId)))
            ->with(['business', 'media']);

        if ($this->latitude === null || $this->longitude === null) {
            return $query
                ->orderByDesc('created_at')
                ->paginate($this->perPage, ['*'], $this->pageName());
        }

        // "Productos cercanos" (Fase 5.1 del TODO social): un producto no
        // tiene coordenadas propias, hereda las de su negocio — mismo
        // criterio de orden/filtro que `businesses()`.
        $products = $query->get()
            ->each(function (Product $product) {
                $product->distance_km = $product->business->hasCoordinates()
                    ? Distance::kilometers($this->latitude, $this->longitude, $product->business->latitude, $product->business->longitude)
                    : null;
            })
            ->sort(function (Product $first, Product $second) {
                if (($first->distance_km === null) !== ($second->distance_km === null)) {
                    return $first->distance_km === null ? 1 : -1;
                }

                if ($first->distance_km !== null) {
                    return $first->distance_km <=> $second->distance_km;
                }

                return $second->created_at <=> $first->created_at;
            })
            ->values();

        if ($this->radiusKm !== null) {
            $products = $products->filter(fn (Product $product) => $product->distance_km !== null && $product->distance_km <= $this->radiusKm)->values();
        }

        $page = $this->getPage($this->pageName());

        return new LengthAwarePaginator(
            $products->forPage($page, $this->perPage)->values(),
            $products->count(),
            $this->perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $this->pageName(),
            ],
        );
    }

    private function pageName(): string
    {
        return $this->type === 'products' ? 'productos_page' : 'page';
    }
}
