@php
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio')],
        ]),
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
        <div
            x-data="clienteMunicipalityAutodetect({{ \Illuminate\Support\Js::from($autoDetectMunicipalities) }})"
            x-init="init()"
        >
            <x-clientes.search-hero
                :municipality="null"
                :municipalities="$municipalities"
                :query="request('q', '')"
            />

            <form x-ref="autodetectForm" method="POST" action="{{ route('clientes.municipio') }}" class="hidden">
                @csrf
                <input x-ref="municipalityId" type="hidden" name="municipality_id">
            </form>
        </div>

        @push('scripts')
            <script>
                window.clienteMunicipalityAutodetect = function (municipalities) {
                    return {
                        municipalities,
                        detecting: false,
                        maxDistanceKm: 25,
                        init() {
                            if (! Array.isArray(this.municipalities) || this.municipalities.length === 0) {
                                return;
                            }

                            if (window.localStorage?.getItem('cliente-municipality-autodetect') === 'done') {
                                return;
                            }

                            if (! window.isSecureContext || ! ('geolocation' in navigator)) {
                                return;
                            }

                            this.detecting = true;

                            navigator.geolocation.getCurrentPosition(
                                (position) => {
                                    this.detecting = false;

                                    const nearest = this.findNearestMunicipality(
                                        position.coords.latitude,
                                        position.coords.longitude,
                                    );

                                    if (! nearest || nearest.distanceKm > this.maxDistanceKm) {
                                        window.localStorage?.setItem('cliente-municipality-autodetect', 'done');
                                        return;
                                    }

                                    window.localStorage?.setItem('cliente-municipality-autodetect', 'done');
                                    this.$refs.municipalityId.value = nearest.id;
                                    this.$refs.autodetectForm.submit();
                                },
                                () => {
                                    this.detecting = false;
                                    window.localStorage?.setItem('cliente-municipality-autodetect', 'done');
                                },
                                {
                                    enableHighAccuracy: false,
                                    timeout: 5000,
                                    maximumAge: 3600000,
                                },
                            );
                        },
                        findNearestMunicipality(latitude, longitude) {
                            return this.municipalities
                                .map((municipality) => ({
                                    ...municipality,
                                    distanceKm: this.distanceBetween(
                                        latitude,
                                        longitude,
                                        municipality.latitude,
                                        municipality.longitude,
                                    ),
                                }))
                                .sort((a, b) => a.distanceKm - b.distanceKm)[0] ?? null;
                        },
                        distanceBetween(fromLat, fromLng, toLat, toLng) {
                            const toRadians = (degrees) => degrees * (Math.PI / 180);
                            const earthRadiusKm = 6371;
                            const deltaLat = toRadians(toLat - fromLat);
                            const deltaLng = toRadians(toLng - fromLng);
                            const a =
                                Math.sin(deltaLat / 2) ** 2 +
                                Math.cos(toRadians(fromLat)) *
                                    Math.cos(toRadians(toLat)) *
                                    Math.sin(deltaLng / 2) ** 2;

                            return earthRadiusKm * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                        },
                    };
                };
            </script>
        @endpush
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

        @if ($municipality)
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Negocios destacados') }}</flux:heading>
                <flux:link :href="route('buscar', ['municipio' => $municipality->slug])" wire:navigate>{{ __('Ver toda la plaza →') }}</flux:link>
            </div>

            @if ($businesses->isEmpty())
                <x-states.empty
                    title="{{ __('Todavía no hay negocios publicados aquí') }}"
                    description="{{ __('Vuelve pronto — cada semana se suman más emprendedores de :municipio.', ['municipio' => $municipality->name]) }}"
                />
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($businesses as $business)
                        <x-business-card :business="$business" />
                    @endforeach
                </div>
            @endif

            <div class="mt-10 flex flex-col items-center gap-4 rounded-2xl border border-brand-100 bg-brand-50 p-6 sm:flex-row dark:border-brand-900 dark:bg-brand-950">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white">
                    <flux:icon.user-group class="size-6" variant="outline" />
                </div>
                <div class="flex-1 text-center sm:text-left">
                    <flux:heading size="lg">{{ __('Juntos construimos comunidad') }}</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        {{ __('Merkamigo es más que una plataforma: es un punto de encuentro para apoyar, recomendar y crecer juntos.') }}
                    </flux:text>
                </div>
                <div class="flex shrink-0 gap-2">
                    <flux:button variant="ghost" :href="route('emprendedores.bienvenida')" wire:navigate>{{ __('Publica tu negocio') }}</flux:button>
                    <flux:button variant="primary" :href="route('como-funciona')" wire:navigate>{{ __('Conoce cómo funciona') }}</flux:button>
                </div>
            </div>
        @endif
    </div>
</x-layouts::cliente>
