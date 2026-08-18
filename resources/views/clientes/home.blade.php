@php
    $featuredSections = [
        [
            'name' => __('Explorar municipios'),
            'description' => __('Recorre los municipios activos y descubre su oferta local organizada por zona.'),
            'url' => route('municipios'),
        ],
        [
            'name' => __('Ver categorías'),
            'description' => __('Encuentra negocios, productos y servicios agrupados por categoría.'),
            'url' => route('categorias'),
        ],
        [
            'name' => __('Buscar en la plaza'),
            'description' => __('Explora la plaza pública y encuentra negocios cerca de ti.'),
            'url' => route('buscar', ['municipio' => 'todos']),
        ],
        [
            'name' => __('Pídelo'),
            'description' => __('Publica una necesidad y recibe propuestas de negocios de tu comunidad.'),
            'url' => route('pidelo'),
        ],
        [
            'name' => __('Cómo funciona'),
            'description' => __('Entiende cómo comprar, vender y aprovechar Merkamigo paso a paso.'),
            'url' => route('como-funciona'),
        ],
        [
            'name' => __('Crear mi vitrina'),
            'description' => __('Abre tu vitrina digital y empieza a mostrar tu negocio localmente.'),
            'url' => route('emprendedores.bienvenida'),
        ],
    ];

    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio')],
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
    :title="__('Inicio')"
    :description="$municipality
        ? __('Explora negocios, productos y servicios locales en :municipio con Merkamigo.', ['municipio' => $municipality->name])
        : __('Descubre negocios, productos y servicios locales en Bogotá y Sabana Norte con Merkamigo.')"
    :canonical="route('home')"
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

    <div class="mx-auto max-w-7xl px-6 py-8">
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

        <div class="mt-8 rounded-xl  border border-rose-100 bg-rose-50/60 p-6 dark:border-rose-900/40 dark:bg-rose-950/20 sm:p-8">
            <div class="max-w-2xl">
                <span class="flex size-11 items-center justify-center rounded-full bg-brand-600 text-white">
                    <flux:icon.chat-bubble-left-right variant="outline" class="size-6" />
                </span>
                <flux:heading size="lg" class="mt-5">{{ __('¿No encuentras lo que necesitas?') }}</flux:heading>
                <flux:text class="mt-3 text-zinc-600 dark:text-zinc-300">
                    {{ __('Publica tu solicitud y recibe propuestas de negocios cercanos listos para ayudar.') }}
                </flux:text>
                <flux:button variant="primary" :href="route('pidelo.nueva')" wire:navigate class="mt-5 w-fit">
                    {{ __('Publicar una solicitud') }}
                </flux:button>
            </div>
        </div>

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

        <div class="mt-10 overflow-hidden rounded-xl  border border-rose-100 bg-rose-50/60 dark:border-rose-900/40 dark:bg-rose-950/20">
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

        <section class="mt-10">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Explora Merkamigo') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Accesos rápidos a las secciones principales') }}</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($featuredSections as $section)
                    <a
                        href="{{ $section['url'] }}"
                        wire:navigate
                        class="group rounded-xl border border-zinc-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-brand-500/50"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold tracking-tight text-zinc-950 transition group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">
                                    {{ $section['name'] }}
                                </h2>
                                <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                                    {{ $section['description'] }}
                                </p>
                            </div>

                            <span class="mt-1 text-brand-500 transition group-hover:translate-x-0.5">→</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts::cliente>
