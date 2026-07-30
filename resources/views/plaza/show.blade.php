@php
    $pageTitle = $category
        ? __(':categoria en :municipio', ['categoria' => $category->name, 'municipio' => $municipio->name])
        : __('Plaza de :municipio', ['municipio' => $municipio->name]);
    $pageDescription = $category
        ? __('Explora negocios, productos y servicios de :categoria en :municipio.', ['categoria' => $category->name, 'municipio' => $municipio->name])
        : __('Explora negocios, productos y servicios locales en :municipio.', ['municipio' => $municipio->name]);
    $canonical = $category ? route('plaza.category', [$municipio, $category]) : route('plaza.show', $municipio);
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb(array_values(array_filter([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => $municipio->name, 'url' => route('plaza.show', $municipio)],
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
        :query="request('q', '')"
    />

    <div class="mx-auto max-w-6xl px-6 py-8">
        <div class="mb-8">
            <x-category-icons
                :categories="$categories"
                :active-category="$category"
                :all-url="route('plaza.show', $municipio)"
                :url-for="fn ($cat) => route('plaza.category', [$municipio, $cat])"
            />
        </div>

        <form method="GET" action="{{ $category ? route('plaza.category', [$municipio, $category]) : route('plaza.show', $municipio) }}" class="mb-6 flex flex-wrap items-center gap-2">
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

            <x-clientes.near-me-toggle :near="$near" />

            @if ($zone || $near)
                <flux:button size="sm" variant="ghost" :href="$category ? route('plaza.category', [$municipio, $category]) : route('plaza.show', $municipio)" wire:navigate>
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
                <flux:link :href="route('plaza.show', $municipio)" wire:navigate>{{ __('Ver toda la plaza →') }}</flux:link>
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

        @if ($products->isNotEmpty())
            <div class="mt-10">
                <form method="GET" action="{{ $category ? route('plaza.category', [$municipio, $category]) : route('plaza.show', $municipio) }}" class="mb-4 flex items-center justify-between">
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
