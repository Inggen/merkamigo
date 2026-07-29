@props([
    'municipality',
    'municipalities',
    'category' => null,
    'query' => '',
    'title' => __('Descubre lo local, conecta con tu comunidad'),
    'description' => null,
])

@php
    $description ??= $category
        ? __('Explora :categoria en :municipio y encuentra negocios, productos y servicios cerca de ti.', [
            'categoria' => $category->name,
            'municipio' => $municipality->name,
        ])
        : __('Mostrando :municipio. Apoya negocios de tu area y encuentra lo que necesitas, cerca de ti.', [
            'municipio' => $municipality->name,
        ]);

    $heroBackground = $municipality->coverUrl() ?? asset('images/backgrounds/fondo-buscador-principal.webp');
@endphp

<div
    class="relative overflow-hidden bg-cover bg-center px-6 py-12 text-white sm:py-14"
    style="background-image: url('{{ $heroBackground }}')"
>
    <div class="absolute inset-0 bg-gradient-to-t from-brand-950/85 via-brand-900/60 to-brand-900/30"></div>

    <div class="relative mx-auto max-w-6xl">
        <flux:heading size="xl" class="text-3xl text-white sm:text-4xl">{{ $title }}</flux:heading>
        <flux:text class="mt-2 max-w-xl text-brand-100">
            {{ $description }}
        </flux:text>

        <div
            x-data="{ selectedMunicipalityId: '{{ $municipality->id }}', selectedMunicipalityName: '{{ e($municipality->name) }}' }"
            class="mt-6 flex flex-col gap-2 rounded-2xl bg-white p-2 shadow-lg sm:flex-row sm:items-center dark:bg-zinc-800"
        >
            <form id="cliente-search-form" method="GET" action="{{ route('buscar') }}" class="flex min-w-0 flex-1 items-center gap-2 px-2">
                <flux:icon.magnifying-glass class="size-5 shrink-0 text-zinc-400" variant="outline" />
                <input
                    type="text"
                    name="q"
                    value="{{ $query }}"
                    placeholder="{{ __('Buscar negocios, productos o servicios...') }}"
                    class="w-full border-0 bg-transparent py-2.5 text-sm text-carbon placeholder:text-zinc-400 focus:outline-none dark:text-white"
                >
                <input type="hidden" name="municipio" x-bind:value="selectedMunicipalityId">
                @if ($category)
                    <input type="hidden" name="categoria" value="{{ $category->id }}">
                @endif
            </form>

            <div class="hidden h-10 w-px bg-zinc-100 sm:block dark:bg-zinc-700"></div>

            <flux:dropdown class="shrink-0 border-t border-zinc-100 pt-2 sm:border-t-0 sm:pt-0 dark:border-zinc-700">
                <flux:button variant="ghost" icon="map-pin" icon-trailing="chevron-down" class="w-full justify-start sm:w-auto sm:min-w-44">
                    <span x-text="selectedMunicipalityName"></span>
                </flux:button>
                <flux:menu>
                    @foreach ($municipalities as $option)
                        <flux:menu.item
                            as="button"
                            type="button"
                            x-on:click="selectedMunicipalityId = '{{ $option->id }}'; selectedMunicipalityName = '{{ e($option->name) }}'"
                            :icon="$municipality->id === $option->id ? 'check' : null"
                            class="w-full cursor-pointer"
                        >
                            {{ $option->name }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>

            <button
                type="submit"
                form="cliente-search-form"
                class="inline-flex shrink-0 items-center justify-center rounded-xl bg-brand-600 px-5 py-3 text-white transition hover:bg-brand-700"
            >
                <flux:icon.magnifying-glass class="size-5" variant="outline" />
            </button>
        </div>
    </div>
</div>
