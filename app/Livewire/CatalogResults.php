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

    public bool $onlyAvailable = false;

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
        return Product::query()
            ->where('status', 'publicado')
            ->when($this->onlyAvailable, fn (Builder $query) => $query->where('is_available', true))
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
            ->with(['business', 'media'])
            ->orderByDesc('created_at')
            ->paginate($this->perPage, ['*'], $this->pageName());
    }

    private function pageName(): string
    {
        return $this->type === 'products' ? 'productos_page' : 'page';
    }
}
