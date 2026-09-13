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
        :title="__('Descubre lo mejor de tu municipio. Compra local, apoya tu comunidad')"
        :description="$selectedMunicipality
            ? __('Mostrando :municipio. Apoya negocios de tu area y encuentra lo que necesitas, cerca de ti.', ['municipio' => $selectedMunicipality->name])
            : __('Miles de negocios, productos y servicios cerca de ti.')"
    />

    <div class="relative z-20 mx-auto -mt-10 max-w-7xl px-6 pb-8">
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

        <h2 id="nuevos-en-la-plaza" class="mb-6 scroll-mt-24 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ __('Nuevos en la plaza') }}</h2>

        @if ($businesses->isEmpty())
            <x-states.empty title="{{ __('No encontramos resultados') }}" description="{{ __('Intenta con otro nombre, categoría o municipio.') }}" />
        @else
            <livewire:catalog-results
                type="businesses"
                :municipality-id="$selectedMunicipality?->id"
                :category-id="$selectedCategory?->id"
                :query="$query"
                :latitude="$near['lat'] ?? null"
                :longitude="$near['lng'] ?? null"
                :per-page="12"
            />
        @endif

        <x-cta.pidelo />

        @if ($openNeeds->isNotEmpty())
            <div class="mt-10 rounded-xl  border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 sm:p-8">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <flux:heading size="base">
                        {{ $selectedMunicipality ? __('Solicitudes activas en :municipio', ['municipio' => $selectedMunicipality->name]) : __('Solicitudes activas') }}
                    </flux:heading>
                    <flux:link :href="route('pidelo', $selectedMunicipality ? ['municipio' => $selectedMunicipality->slug] : [])" wire:navigate class="shrink-0 text-sm">{{ __('Ver todas →') }}</flux:link>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ($openNeeds as $need)
                        <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:text class="font-semibold text-zinc-950 dark:text-white">{{ $need->title }}</flux:text>

                            @if ($need->category)
                                <flux:text class="mt-0.5 block text-sm text-zinc-500 dark:text-zinc-400">{{ $need->category->name }}</flux:text>
                            @endif

                            <flux:text class="mt-3 block text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Hace :time', ['time' => $need->published_at?->diffForHumans(null, true)]) }}
                            </flux:text>

                            <div class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                <flux:icon.clock variant="outline" class="size-4" />
                                <span>{{ trans_choice(':count propuesta|:count propuestas', $need->offers_count, ['count' => $need->offers_count]) }}</span>
                            </div>

                            @if ($need->budget)
                                <span class="mt-3 flex items-center justify-center rounded-full bg-rose-50 px-3 py-1.5 text-center text-xs font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
                                    {{ __('Presupuesto: :amount', ['amount' => '$'.number_format((float) $need->budget, 0, ',', '.')]) }}
                                </span>
                            @endif

                            <div class="mt-3 text-center">
                                <flux:link :href="route('pidelo.show', $need)" wire:navigate class="text-sm font-medium">{{ __('Ver solicitud →') }}</flux:link>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($products->isNotEmpty())
            <div class="mt-10">
                <form method="GET" action="{{ url()->current() }}" class="mb-4 flex items-center justify-between gap-3">
                    @if ($query !== '')
                        <input type="hidden" name="q" value="{{ $query }}">
                    @endif

                    <flux:heading size="lg">{{ __('Productos para ti') }}</flux:heading>

                    <label class="flex shrink-0 items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <input type="checkbox" name="disponibles" value="1" {{ $onlyAvailable ? 'checked' : '' }} onchange="this.form.submit()">
                        {{ __('Solo disponibles') }}
                    </label>
                </form>

                <livewire:catalog-results
                    type="products"
                    :municipality-id="$selectedMunicipality?->id"
                    :category-id="$selectedCategory?->id"
                    :query="$query"
                    :only-available="$onlyAvailable"
                    :per-page="8"
                />
            </div>
        @endif

        <x-cta.crear-vitrina />
    </div>
</x-layouts::cliente>
