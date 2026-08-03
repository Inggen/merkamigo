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
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($businesses as $business)
                    <x-business-card :business="$business" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $businesses->links() }}
            </div>
        @endif

        <div class="mt-10 overflow-hidden rounded-[28px] border border-rose-100 bg-rose-50/60 dark:border-rose-900/40 dark:bg-rose-950/20">
            <div class="grid gap-0 lg:grid-cols-2">
                <div class="flex flex-col justify-center gap-4 p-6 sm:p-8">
                    <span class="flex size-11 items-center justify-center rounded-full bg-brand-600 text-white">
                        <flux:icon.chat-bubble-left-right variant="outline" class="size-6" />
                    </span>
                    <flux:heading size="lg">{{ __('¿No encuentras lo que necesitas?') }}</flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-300">
                        {{ __('Publica tu solicitud y recibe propuestas de negocios cercanos listos para ayudar.') }}
                    </flux:text>
                    <flux:button variant="primary" :href="route('pidelo.nueva')" wire:navigate class="w-fit">
                        {{ __('Publicar una solicitud') }}
                    </flux:button>
                </div>

                @if ($openNeeds->isNotEmpty())
                    <div class="border-t border-rose-100 bg-white p-6 dark:border-rose-900/40 dark:bg-zinc-900 lg:border-t-0 lg:border-l sm:p-8">
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
            </div>
        </div>

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

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($products as $product)
                        @include('vitrinas.partials.product-card', ['business' => $product->business, 'product' => $product, 'showBusinessName' => true])
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            </div>
        @endif

        <div class="mt-10 overflow-hidden rounded-[28px] border border-rose-100 bg-rose-50/60 dark:border-rose-900/40 dark:bg-rose-950/20">
            <div class="flex flex-col items-center gap-6 p-8 sm:flex-row sm:justify-between sm:p-10">
                <div class="max-w-lg text-center sm:text-left">
                    <flux:heading size="lg">{{ __('Haz visible tu negocio en tu comunidad') }}</flux:heading>
                    <flux:text class="mt-2 text-zinc-600 dark:text-zinc-300">
                        {{ __('Crea tu vitrina gratis y llega a más personas de tu zona que ya están comprando local.') }}
                    </flux:text>
                    <flux:button variant="primary" :href="route('emprendedores.bienvenida')" wire:navigate class="mt-4 w-fit">
                        {{ __('Crear mi vitrina gratis') }}
                    </flux:button>
                </div>
                <img src="{{ asset('images/fondo-login-admin.svg') }}" alt="" class="hidden w-full shrink-0 opacity-50 sm:block" style="max-width: 700px" loading="lazy">
            </div>
        </div>
    </div>
</x-layouts::cliente>
