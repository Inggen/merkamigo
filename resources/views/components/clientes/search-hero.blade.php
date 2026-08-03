@props([
    'municipality' => null,
    'municipalities',
    'category' => null,
    'categoryId' => null,
    'categorySlug' => null,
    'query' => '',
    'title' => __('Descubre lo local, conecta con tu comunidad'),
    'description' => null,
    'near' => null,
    'showImmersiveCta' => false,
])

@php
    $selectedCategoryId = $categoryId ?? $category?->id;
    $selectedCategorySlug = $categorySlug ?? $category?->slug;
    $fallbackBackground = asset('images/backgrounds/fondo-buscador-principal.webp');
    $allMunicipalitiesLabel = __('Todos');
    $fallbackMedia = ['type' => 'image', 'url' => $fallbackBackground];

    $description ??= $municipality
        ? ($category
            ? __('Explora :categoria en :municipio y encuentra negocios, productos y servicios cerca de ti.', [
                'categoria' => $category->name,
                'municipio' => $municipality->name,
            ])
            : __('Mostrando :municipio. Apoya negocios de tu area y encuentra lo que necesitas, cerca de ti.', [
                'municipio' => $municipality->name,
            ]))
        : __('Busca negocios, productos y servicios por municipio o cerca de ti.');

    // El fondo del hero debe venir del municipio configurado en admin.
    // No reemplazar por assets inmersivos hardcodeados ni por fondos mock.
    $heroMedia = $municipality?->searchHeroMedia()
        ?? $fallbackMedia;
    $immersiveUrl = $showImmersiveCta ? $municipality?->immersiveLabUrl() : null;
    $selectedMunicipalityId = (string) ($municipality?->id ?? '');
    $selectedMunicipalityName = $municipality?->name ?? $allMunicipalitiesLabel;
    $municipalityOptions = $municipalities->map(fn ($option) => [
        'id' => (string) $option->id,
        'name' => $option->name,
        'slug' => $option->slug,
        'media' => $option->searchHeroMedia() ?? $fallbackMedia,
        'immersiveUrl' => $showImmersiveCta ? $option->immersiveLabUrl() : null,
    ])->values();
@endphp

<div
    x-data="{
        offset: 0,
        appliedMunicipalityId: @js($selectedMunicipalityId),
        appliedMunicipalityName: @js($selectedMunicipalityName),
        selectedMunicipalityId: @js($selectedMunicipalityId),
        selectedMunicipalityName: @js($selectedMunicipalityName),
        selectedMunicipalitySlug: @js($municipality?->slug ?? ''),
        selectedCategorySlug: @js($selectedCategorySlug),
        currentMediaType: @js($heroMedia['type']),
        currentMediaUrl: @js($heroMedia['url']),
        currentImmersiveUrl: @js($immersiveUrl),
        municipalities: {{ \Illuminate\Support\Js::from($municipalityOptions) }},
        fallbackMedia: {{ \Illuminate\Support\Js::from($fallbackMedia) }},
        allMunicipalitiesLabel: @js($allMunicipalitiesLabel),
        baseSearchUrl: @js(route('buscar')),
        selectMunicipality(id, name, slug = '') {
            this.selectedMunicipalityId = id;
            this.selectedMunicipalityName = name;
            this.selectedMunicipalitySlug = slug;
        },
        buildSearchAction() {
            const municipalitySegment = this.selectedMunicipalitySlug || (this.selectedCategorySlug ? 'todos' : '');
            const categorySegment = this.selectedCategorySlug || '';
            const segments = [municipalitySegment, categorySegment].filter(Boolean);

            return segments.length > 0
                ? `${this.baseSearchUrl.replace(/\/$/, '')}/${segments.join('/')}`
                : this.baseSearchUrl;
        },
        cleanupEmptyFields(form) {
            form.querySelectorAll('input[name]').forEach((input) => {
                if ((input.value ?? '').trim() === '') {
                    input.dataset.originalName = input.name;
                    input.removeAttribute('name');
                    return;
                }

                if (input.dataset.originalName) {
                    input.name = input.dataset.originalName;
                    delete input.dataset.originalName;
                }
            });
        },
    }"
    x-on:scroll.window="offset = Math.min(72, Math.max(0, window.scrollY * 0.18))"
    class="relative overflow-hidden px-6 py-12 text-white sm:py-[calc(var(--spacing)*22)]"
>
    @if ($immersiveUrl)
        <a
            href="{{ $immersiveUrl }}"
            class="absolute inset-0 z-0 block"
            aria-label="{{ __('Abrir experiencia inmersiva del municipio seleccionado') }}"
            title="{{ __('Abrir experiencia inmersiva') }}"
        ></a>
    @endif
    <template x-if="currentMediaType === 'video'">
        <video
            class="absolute inset-0 z-0 h-full w-full scale-110 object-cover"
            x-bind:style="`object-position: center calc(50% + ${offset}px); transform: scale(1.1);`"
            x-bind:src="currentMediaUrl"
            autoplay
            muted
            loop
            playsinline
            preload="metadata"
        ></video>
    </template>
    <template x-if="currentMediaType !== 'video'">
        <div
            class="absolute inset-0 z-0 scale-110 bg-cover bg-center bg-no-repeat will-change-transform"
            x-bind:style="`background-image: url('${currentMediaUrl}'); background-position: center calc(50% + ${offset}px); transform: scale(1.1);`"
        ></div>
    </template>
    <div class="absolute inset-0 z-0 bg-gradient-to-t from-brand-950/85 via-brand-900/60 to-brand-900/30"></div>

    <div class="relative z-10 mx-auto max-w-7xl px-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-heading text-3xl font-semibold text-white sm:text-4xl">{{ $title }}</h1>
                <flux:text class="mt-2 max-w-xl text-brand-100">
                    {{ $description }}
                </flux:text>
            </div>

            @if ($immersiveUrl)
                <a
                    href="{{ $immersiveUrl }}"
                    class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-sm font-medium text-white backdrop-blur transition hover:bg-white/16"
                >
                    <flux:icon.cube-transparent class="size-4" variant="outline" />
                    {{ __('Ver experiencia inmersiva') }}
                </a>
            @endif
        </div>

        <form
            id="cliente-search-form"
            method="GET"
            x-bind:action="buildSearchAction()"
            x-on:submit="cleanupEmptyFields($el)"
            class="mt-6 flex flex-col gap-2 rounded-2xl bg-white p-2 shadow-lg sm:flex-row sm:flex-wrap sm:items-center dark:bg-zinc-800"
        >
            <div class="flex min-w-0 flex-1 items-center gap-2 px-2">
                <flux:icon.magnifying-glass class="size-5 shrink-0 text-zinc-400" variant="outline" />
                <input
                    type="text"
                    name="q"
                    value="{{ $query }}"
                    placeholder="{{ __('Buscar negocios, productos o servicios...') }}"
                    class="w-full border-0 bg-transparent py-2.5 text-sm text-carbon placeholder:text-zinc-400 focus:outline-none dark:text-white"
                >
            </div>

            <div class="flex w-full flex-col gap-2 border-t border-zinc-100 pt-2 sm:w-auto sm:flex-row sm:items-center sm:border-t-0 sm:pt-0 dark:border-zinc-700">
                <div class="hidden h-10 w-px bg-zinc-100 sm:block dark:bg-zinc-700"></div>

                <div class="flex w-full items-stretch gap-2 sm:w-auto sm:items-center">
                    <flux:dropdown class="min-w-0 flex-1 shrink sm:flex-none">
                        <flux:button variant="ghost" icon="map-pin" icon-trailing="chevron-down" class="w-full justify-start sm:w-auto sm:min-w-44">
                            <span x-text="selectedMunicipalityName" class="truncate"></span>
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item
                                as="button"
                                type="button"
                                x-on:click="selectMunicipality('', allMunicipalitiesLabel, '')"
                                class="w-full cursor-pointer"
                            >
                                <span class="flex w-full items-center justify-between gap-2">
                                    {{ $allMunicipalitiesLabel }}
                                    <flux:icon.check x-show="selectedMunicipalityId === ''" x-cloak class="size-4" variant="outline" />
                                </span>
                            </flux:menu.item>

                            @foreach ($municipalities as $option)
                                <flux:menu.item
                                    as="button"
                                    type="button"
                                    x-on:click="selectMunicipality('{{ $option->id }}', '{{ e($option->name) }}', '{{ $option->slug }}')"
                                    class="w-full cursor-pointer"
                                >
                                    <span class="flex w-full items-center justify-between gap-2">
                                        {{ $option->name }}
                                        <flux:icon.check x-show="selectedMunicipalityId === '{{ $option->id }}'" x-cloak class="size-4" variant="outline" />
                                    </span>
                                </flux:menu.item>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>

                    <div class="shrink-0">
                        <x-clientes.near-me-toggle :near="$near" compact />
                    </div>
                </div>
            </div>

            <button
                type="submit"
                class="inline-flex w-full shrink-0 items-center justify-center rounded-xl bg-brand-600 px-5 py-3 text-white transition hover:bg-brand-700 sm:w-auto"
            >
                <flux:icon.magnifying-glass class="size-5" variant="outline" />
            </button>
        </form>
    </div>
</div>
