@php
    $pageTitle = $category
        ? __(':categoria en :municipio', ['categoria' => $category->name, 'municipio' => $municipio->name])
        : __('Plaza de :municipio', ['municipio' => $municipio->name]);
    $pageDescription = $category
        ? __('Explora negocios, productos y servicios de :categoria en :municipio.', ['categoria' => $category->name, 'municipio' => $municipio->name])
        : __('Explora negocios, productos y servicios locales en :municipio.', ['municipio' => $municipio->name]);
    $canonical = $category
        ? route('buscar', ['municipio' => $municipio->slug, 'categoria' => $category->slug])
        : route('buscar', ['municipio' => $municipio->slug]);
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb(array_values(array_filter([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => $municipio->name, 'url' => route('buscar', ['municipio' => $municipio->slug])],
            $category ? ['name' => $category->name] : null,
        ]))),
        \App\Support\Seo\SchemaBuilder::itemList(
            $featured->concat($businesses->getCollection())->take(18)->map(fn ($business) => [
                'name' => $business->name,
                'url' => route('vitrinas.show', $business),
                'image' => $business->storefront?->coverUrl() ?? $business->logoUrl(),
            ])->all(),
            $pageTitle,
        ),
    ];
@endphp

<x-layouts::cliente
    :title="$pageTitle"
    :description="$pageDescription"
    :canonical="$canonical"
    page-schema-type="CollectionPage"
    :page-schema-data="['about' => $municipio->name]"
    :schema-graph="$schemaGraph"
    :show-municipality-selector="false"
>
    <x-clientes.search-hero
        :municipality="$municipio"
        :municipalities="$municipalities"
        :category="$category"
        :category-id="$category?->id"
        :query="request('q', '')"
        :near="$near"
        :show-immersive-cta="true"
    />

    <div class="mx-auto max-w-7xl px-6 py-8">
        <div class="mb-8">
            <x-category-icons
                :categories="$categories"
                :active-category="$category"
                :all-url="route('buscar', ['municipio' => $municipio->slug])"
                :url-for="fn ($cat) => route('buscar', ['municipio' => $municipio->slug, 'categoria' => $cat->slug])"
            />
        </div>

        <form method="GET" action="{{ $category ? route('buscar', ['municipio' => $municipio->slug, 'categoria' => $category->slug]) : route('buscar', ['municipio' => $municipio->slug]) }}" class="mb-6 flex flex-wrap items-center gap-2">
            @if ($onlyAvailable)
                <input type="hidden" name="disponibles" value="1">
            @endif

            @if ($zones->isNotEmpty())
                <flux:select name="zona" class="max-w-48">
                    <flux:select.option value="">{{ __('Todas las zonas') }}</flux:select.option>
                    @foreach ($zones as $option)
                        <flux:select.option value="{{ $option }}" :selected="$zone === $option">{{ $option }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:button type="submit" size="sm" variant="ghost">{{ __('Filtrar por zona') }}</flux:button>
            @endif

            @if ($zone || $near)
                <flux:button size="sm" variant="ghost" :href="$category ? route('buscar', ['municipio' => $municipio->slug, 'categoria' => $category->slug]) : route('buscar', ['municipio' => $municipio->slug]) }}" wire:navigate>
                    {{ __('Quitar filtros') }}
                </flux:button>
            @endif
        </form>

        @if ($featured->isNotEmpty())
            <div class="mb-10">
                <flux:heading size="lg" class="mb-4">{{ __('Destacados') }}</flux:heading>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featured as $business)
                        <x-business-card :business="$business" />
                    @endforeach
                </div>
            </div>
        @endif

        @if ($businesses->isEmpty() && $featured->isEmpty())
            <x-states.empty
                title="{{ __('Todavía no hay negocios publicados aquí') }}"
                description="{{ __('Vuelve pronto — cada semana se suman más emprendedores de :municipio.', ['municipio' => $municipio->name]) }}"
            />
        @elseif ($businesses->isNotEmpty())
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">
                    @if ($category)
                        {{ __('Negocios en :categoria', ['categoria' => $category->name]) }}
                    @elseif ($near)
                        {{ __('Cerca de ti') }}
                    @else
                        {{ __('Nuevos') }}
                    @endif
                </flux:heading>
                <flux:link :href="route('buscar', ['municipio' => $municipio->slug])" wire:navigate>{{ __('Ver toda la plaza →') }}</flux:link>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($businesses as $business)
                    <x-business-card :business="$business" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $businesses->links() }}
            </div>
        @endif

        @if ($openNeeds->isNotEmpty())
            <div class="mt-10">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Solicitudes actuales en :municipio', ['municipio' => $municipio->name]) }}</flux:heading>
                    <flux:link :href="route('pidelo')" wire:navigate>{{ __('Ver todas →') }}</flux:link>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($openNeeds as $need)
                        <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:heading size="base">{{ $need->title }}</flux:heading>
                            <div class="mt-1 flex flex-wrap items-center gap-x-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                @if ($need->category)
                                    <span>{{ $need->category->name }}</span>
                                    <span>·</span>
                                @endif
                                <span>{{ $need->published_at?->diffForHumans() }}</span>
                            </div>
                            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ trans_choice(':count propuesta recibida|:count propuestas recibidas', $need->offers_count, ['count' => $need->offers_count]) }}
                            </flux:text>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($products->isNotEmpty())
            <div class="mt-10">
                <form method="GET" action="{{ $category ? route('buscar', ['municipio' => $municipio->slug, 'categoria' => $category->slug]) : route('buscar', ['municipio' => $municipio->slug]) }}" class="mb-4 flex items-center justify-between">
                    @if ($zone)
                        <input type="hidden" name="zona" value="{{ $zone }}">
                    @endif

                    <flux:heading size="lg">{{ __('Productos') }}</flux:heading>

                    <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <input type="checkbox" name="disponibles" value="1" {{ $onlyAvailable ? 'checked' : '' }} onchange="this.form.submit()">
                        {{ __('Solo disponibles') }}
                    </label>
                </form>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($products as $product)
                        @include('vitrinas.partials.product-card', ['business' => $product->business, 'product' => $product])
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            </div>
        @endif
    </div>
</x-layouts::cliente>
