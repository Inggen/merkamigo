@php
    $featuredSections = [
        [
            'name' => __('Explorar municipios'),
            'description' => __('Recorre los municipios activos y descubre su oferta local organizada por zona.'),
            'url' => route('municipios'),
            'icon' => 'map-pin',
            'label' => __('Explora tu zona'),
        ],
        [
            'name' => __('Ver categorías'),
            'description' => __('Encuentra negocios, productos y servicios agrupados por categoría.'),
            'url' => route('categorias'),
            'icon' => 'squares-2x2',
            'label' => __('Compra local'),
        ],
        [
            'name' => __('Buscar en la plaza'),
            'description' => __('Explora la plaza pública y encuentra negocios cerca de ti.'),
            'url' => route('buscar', ['municipio' => 'todos']),
            'icon' => 'magnifying-glass',
            'label' => __('Encuentra negocios'),
        ],
        [
            'name' => __('Pídelo'),
            'description' => __('Publica una necesidad y recibe propuestas de negocios de tu comunidad.'),
            'url' => route('pidelo'),
            'icon' => 'megaphone',
            'label' => __('Publica lo que buscas'),
        ],
        [
            'name' => __('Cómo funciona'),
            'description' => __('Entiende cómo comprar, vender y aprovechar Merkamigo paso a paso.'),
            'url' => route('como-funciona'),
            'icon' => 'question-mark-circle',
            'label' => __('Conoce la plataforma'),
        ],
        [
            'name' => __('Crear mi vitrina'),
            'description' => __('Abre tu vitrina digital y empieza a mostrar tu negocio localmente.'),
            'url' => route('emprendedores.bienvenida'),
            'icon' => 'building-storefront',
            'label' => __('Empieza a vender'),
        ],
    ];

    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Explorar')],
        ]),
        \App\Support\Seo\SchemaBuilder::siteNavigation(
            $featuredSections,
            __('Secciones destacadas de Inicio'),
        ),
    ];

    if ($municipality) {
        $schemaGraph[] = \App\Support\Seo\SchemaBuilder::itemList(
            $businesses->take(12)->map(fn ($business) => [
                'name' => $business->name,
                'url' => route('vitrinas.show', $business),
                'image' => $business->storefront?->coverUrl() ?? $business->logoUrl(),
            ])->all(),
            __('Negocios destacados en :municipio', ['municipio' => $municipality->name]),
        );
    } else {
        $schemaGraph[] = \App\Support\Seo\SchemaBuilder::itemList(
            $municipalities->map(fn ($option) => [
                'name' => $option->name,
                'url' => route('buscar', ['municipio' => $option->slug]),
            ])->all(),
            __('Municipios activos'),
        );
    }
@endphp

<x-layouts::cliente
    :title="__('Explorar Merkamigo: negocios y productos locales cerca de ti')"
    :description="$municipality
        ? __('Explora negocios, productos y servicios locales en :municipio con Merkamigo.', ['municipio' => $municipality->name])
        : __('Descubre negocios, productos y servicios locales en Bogotá y Sabana Norte con Merkamigo.')"
    :canonical="route('explorar')"
    :page-schema-type="$municipality ? 'CollectionPage' : 'WebPage'"
    :schema-graph="$schemaGraph"
>
    @if (! $municipality)
        <x-clientes.search-hero
            :municipality="null"
            :municipalities="$municipalities"
            :query="request('q', '')"
        />
    @else
        <x-clientes.search-hero
            :municipality="$municipality"
            :municipalities="$municipalities"
            :query="request('q', '')"
        />
    @endif

    <div class="relative z-20 mx-auto -mt-10 max-w-7xl px-6 pb-8">
        <div class="mb-8">
            <x-category-icons
                :categories="$categories"
                :all-url="$municipality ? route('buscar', ['municipio' => $municipality->slug]) : route('buscar')"
                :url-for="fn ($category) => $municipality
                    ? route('buscar', ['municipio' => $municipality->slug, 'categoria' => $category->slug])
                    : route('buscar', ['municipio' => 'todos', 'categoria' => $category->slug])"
            />
        </div>

        <div id="nuevos-en-la-plaza" class="mb-4 flex scroll-mt-24 items-center justify-between">
            <flux:heading size="lg">{{ __('Nuevos en la plaza') }}</flux:heading>
            <flux:link :href="$municipality ? route('buscar', ['municipio' => $municipality->slug]) : route('buscar', ['municipio' => 'todos'])" wire:navigate class="text-sm">{{ __('Ver toda la plaza →') }}</flux:link>
        </div>

        @if ($businesses->isEmpty())
            <x-states.empty
                title="{{ __('Todavía no hay negocios publicados aquí') }}"
                description="{{ $municipality
                    ? __('Vuelve pronto — cada semana se suman más emprendedores de :municipio.', ['municipio' => $municipality->name])
                    : __('Vuelve pronto — cada semana se suman más emprendedores a Merkamigo.') }}"
            />
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($businesses as $business)
                    <x-business-card :business="$business" />
                @endforeach
            </div>
        @endif

        <x-cta.pidelo />

        @if ($openNeeds->isNotEmpty())
            <div class="mt-10 rounded-xl  border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 sm:p-8">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <flux:heading size="base">
                        {{ $municipality ? __('Solicitudes activas en :municipio', ['municipio' => $municipality->name]) : __('Solicitudes activas') }}
                    </flux:heading>
                    <flux:link :href="route('pidelo', $municipality ? ['municipio' => $municipality->slug] : [])" wire:navigate class="shrink-0 text-sm">{{ __('Ver todas →') }}</flux:link>
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
                <flux:heading size="lg" class="mb-4">{{ __('Productos para ti') }}</flux:heading>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($products as $product)
                        @include('vitrinas.partials.product-card', ['business' => $product->business, 'product' => $product, 'showBusinessName' => true])
                    @endforeach
                </div>
            </div>
        @endif

        <x-cta.crear-vitrina />

        <section class="mt-12">
            <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="h-11 w-1 rounded-full bg-brand-600"></span>
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-[0.16em] text-brand-600 dark:text-brand-400">{{ __('Descubre lo local') }}</span>
                        <flux:heading size="xl" class="mt-1 tracking-tight">{{ __('Explora Merkamigo') }}</flux:heading>
                    </div>
                </div>
                <flux:text class="max-w-md text-sm leading-6 text-zinc-500 dark:text-zinc-400 sm:text-right">{{ __('Accesos rápidos para descubrir, comprar, publicar o empezar a vender.') }}</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($featuredSections as $section)
                    <a
                        href="{{ $section['url'] }}"
                        wire:navigate
                        class="group relative isolate min-h-52 overflow-hidden rounded-3xl border border-zinc-200/90 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-brand-300 hover:shadow-xl hover:shadow-brand-900/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:border-white/10 dark:bg-zinc-900/90 dark:hover:border-brand-500/50 dark:hover:shadow-black/30 dark:focus-visible:ring-offset-zinc-950"
                    >
                        <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-600 via-red-500 to-orange-400 opacity-80 transition group-hover:opacity-100"></span>
                        <span class="absolute -bottom-16 -right-12 -z-10 size-40 rounded-full bg-brand-500/5 blur-2xl transition duration-500 group-hover:scale-125 group-hover:bg-brand-500/10 dark:bg-brand-500/10"></span>

                        <div class="flex items-start justify-between gap-4">
                            <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 ring-1 ring-brand-100 transition duration-300 group-hover:scale-105 group-hover:bg-brand-600 group-hover:text-white dark:bg-brand-500/10 dark:text-brand-300 dark:ring-brand-500/20 dark:group-hover:bg-brand-600 dark:group-hover:text-white">
                                <flux:icon :name="$section['icon']" class="size-6" variant="outline" />
                            </span>

                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-zinc-200 bg-zinc-50 text-brand-600 transition duration-300 group-hover:translate-x-1 group-hover:border-brand-200 group-hover:bg-brand-50 dark:border-white/10 dark:bg-white/5 dark:text-brand-300 dark:group-hover:border-brand-500/30 dark:group-hover:bg-brand-500/10">
                                <flux:icon.arrow-up-right class="size-5" variant="outline" />
                            </span>
                        </div>

                        <div class="mt-6">
                            <div class="mb-2 flex items-center gap-2">
                                <span class="text-[0.68rem] font-bold uppercase tracking-[0.16em] text-brand-600 dark:text-brand-400">{{ $section['label'] }}</span>
                                <span class="h-px flex-1 bg-gradient-to-r from-brand-200 to-transparent dark:from-brand-500/30"></span>
                                <span class="text-xs font-semibold tabular-nums text-zinc-300 dark:text-zinc-600">0{{ $loop->iteration }}</span>
                            </div>
                            <h2 class="text-xl font-bold tracking-tight text-zinc-950 transition group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">
                                {{ $section['name'] }}
                            </h2>
                            <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                                {{ $section['description'] }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts::cliente>
