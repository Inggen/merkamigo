<x-layouts::cliente :title="__('Inicio')" :show-municipality-selector="! $municipality">
    @if (! $municipality)
        <div
            x-data="clienteMunicipalityAutodetect({{ \Illuminate\Support\Js::from($autoDetectMunicipalities) }})"
            x-init="init()"
            class="relative overflow-hidden bg-cover bg-center px-6 py-16 text-white"
            style="background-image: url('{{ asset('images/backgrounds/fondo-buscador-principal.webp') }}')"
        >
            <div class="absolute inset-0 bg-gradient-to-t from-brand-950/85 via-brand-900/60 to-brand-900/30"></div>

            <div class="relative mx-auto max-w-3xl text-center">
                <flux:heading size="xl" class="text-3xl text-white sm:text-4xl">{{ __('Descubre lo local, conecta con tu comunidad') }}</flux:heading>
                <flux:text class="mt-2 mb-8 text-brand-100">
                    {{ __('Elige tu municipio para ver negocios cercanos.') }}
                </flux:text>

                <div x-show="detecting" x-cloak class="mb-6 text-sm text-brand-100/90">
                    {{ __('Intentando detectar tu municipio para mostrarte lo mas cercano...') }}
                </div>

                <div class="flex flex-wrap justify-center gap-2">
                    @foreach ($municipalities as $option)
                        <form method="POST" action="{{ route('clientes.municipio') }}">
                            @csrf
                            <input type="hidden" name="municipality_id" value="{{ $option->id }}">
                            <button type="submit" class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-brand-700 transition hover:bg-brand-50">
                                {{ $option->name }}
                            </button>
                        </form>
                    @endforeach
                </div>

                <form x-ref="autodetectForm" method="POST" action="{{ route('clientes.municipio') }}" class="hidden">
                    @csrf
                    <input x-ref="municipalityId" type="hidden" name="municipality_id">
                </form>
            </div>
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

        <div class="mx-auto max-w-6xl px-6 py-8">
            <div class="mb-8">
                <x-category-icons
                    :categories="$categories"
                    :all-url="route('plaza.show', $municipality)"
                    :url-for="fn ($category) => route('plaza.category', [$municipality, $category])"
                />
            </div>

            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Negocios destacados') }}</flux:heading>
                <flux:link :href="route('plaza.show', $municipality)" wire:navigate>{{ __('Ver toda la plaza →') }}</flux:link>
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
        </div>
    @endif
</x-layouts::cliente>
