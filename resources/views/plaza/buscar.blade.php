@php
    $pageTitle = __('Buscar');
    $pageDescription = $query !== ''
        ? __('Resultados de búsqueda en Merkamigo para ":query".', ['query' => $query])
        : __('Busca negocios, productos y servicios por municipio o categoría.');
    $activeCategory = $selectedCategory ?? null;
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Buscar')],
        ]),
        \App\Support\Seo\SchemaBuilder::itemList(
            $businesses->getCollection()->take(18)->map(fn ($business) => [
                'name' => $business->name,
                'url' => route('vitrinas.show', $business),
                'image' => $business->storefront?->coverUrl() ?? $business->logoUrl(),
            ])->all(),
            __('Resultados de búsqueda'),
        ),
    ];
@endphp

<x-layouts::cliente
    :title="$pageTitle"
    :description="$pageDescription"
    :canonical="url()->current()"
    robots="noindex,follow"
    page-schema-type="SearchResultsPage"
    :page-schema-data="['query' => $query]"
    :schema-graph="$schemaGraph"
    :show-municipality-selector="false"
>
    <x-clientes.search-hero
        :municipality="$selectedMunicipality"
        :municipalities="$municipalities"
        :category="$selectedCategory"
        :query="$query"
        :near="$near"
        :show-immersive-cta="$selectedMunicipality !== null"
        :title="__('Descubre lo local, conecta con tu comunidad')"
        :description="$selectedMunicipality
            ? __('Mostrando :municipio. Apoya negocios de tu area y encuentra lo que necesitas, cerca de ti.', ['municipio' => $selectedMunicipality->name])
            : __('Busca negocios, productos y servicios por municipio o cerca de ti.')"
    />

    <div class="mx-auto max-w-7xl px-6 py-8">
        <div class="mb-8">
            <x-category-icons
                :categories="$categories"
                :active-category="$activeCategory"
                :all-url="route('buscar', array_filter([
                    'municipio' => $selectedMunicipality?->slug,
                    'q' => $query !== '' ? $query : null,
                    'lat' => $near['lat'] ?? null,
                    'lng' => $near['lng'] ?? null,
                ], fn ($value) => filled($value)))"
                :url-for="fn ($category) => route('buscar', array_filter([
                    'municipio' => $selectedMunicipality?->slug ?: 'todos',
                    'categoria' => $category->slug,
                    'q' => $query !== '' ? $query : null,
                    'lat' => $near['lat'] ?? null,
                    'lng' => $near['lng'] ?? null,
                ], fn ($value) => filled($value)))"
            />
        </div>

        <h2 class="mb-6 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ __('Descubre cerca de ti') }}</h2>

        @if ($businesses->isEmpty())
            <x-states.empty title="{{ __('No encontramos resultados') }}" description="{{ __('Intenta con otro nombre, categoría o municipio.') }}" />
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($businesses as $business)
                    <x-business-card :business="$business" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $businesses->links() }}
            </div>
        @endif
    </div>
</x-layouts::cliente>
