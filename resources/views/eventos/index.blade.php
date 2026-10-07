@php
    $pageTitle = __('Eventos cerca de ti');
    $pageDescription = __('Descubre eventos de negocios locales en Merkamigo: música en vivo, talleres, mercados y más.');
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Eventos')],
        ]),
    ];
    $categoryIcons = [
        'musica' => 'musical-note',
        'taller' => 'user-group',
        'mercado' => 'shopping-bag',
        'gastronomia' => 'sparkles',
        'arte' => 'paint-brush',
        'deporte' => 'trophy',
        'otro' => 'calendar-days',
    ];
@endphp

<x-layouts::cliente
    :title="$pageTitle"
    :description="$pageDescription"
    :canonical="route('eventos')"
    :schema-graph="$schemaGraph"
>
    <section
        class="relative isolate min-h-[350px] bg-cover bg-center sm:min-h-[390px]"
        style="background-image: linear-gradient(90deg, rgba(9, 9, 11, .82) 0%, rgba(9, 9, 11, .52) 38%, rgba(9, 9, 11, .1) 72%), url('{{ asset('images/backgrounds/fondo_banner_eventos.webp') }}');"
    >
        <div class="mx-auto flex min-h-[350px] max-w-7xl items-center px-4 pb-16 pt-10 sm:min-h-[390px] sm:px-6 lg:px-8">
            <div class="max-w-2xl text-white">
                <h1 class="text-4xl font-black tracking-tight drop-shadow-lg sm:text-5xl lg:text-6xl">
                    {{ __('Eventos') }} <span class="text-red-400">{{ __('cerca de ti') }}</span>
                </h1>
                <p class="mt-4 max-w-xl text-lg leading-7 text-white/95 drop-shadow sm:text-xl">
                    {{ __('Descubre experiencias de negocios locales: música en vivo, talleres, mercados y más.') }}
                </p>
            </div>

            @if ($heroMunicipality)
                <div class="absolute bottom-24 right-[8%] hidden items-center gap-3 rounded-2xl border border-white/25 bg-zinc-950/45 px-5 py-3 text-white shadow-xl backdrop-blur-md lg:flex">
                    <flux:icon.map-pin class="size-7 shrink-0" variant="solid" />
                    <div>
                        <p class="font-semibold leading-tight">{{ $heroMunicipality->name }}</p>
                        <p class="text-xs text-white/80">{{ $heroMunicipality->department }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="absolute inset-x-0 bottom-0 z-10 translate-y-1/2 px-4 sm:px-6">
            <form method="GET" action="{{ route('eventos') }}" class="mx-auto grid max-w-7xl gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-xl sm:grid-cols-2 lg:grid-cols-[1fr_1fr_.85fr_auto] dark:border-zinc-700 dark:bg-zinc-900">
                <label class="relative block">
                    <span class="sr-only">{{ __('Filtrar por municipio') }}</span>
                    <flux:icon.map-pin class="pointer-events-none absolute left-4 top-1/2 z-10 size-5 -translate-y-1/2 text-brand-600" variant="outline" />
                    <select name="municipio" class="h-12 w-full appearance-none rounded-xl border border-zinc-200 bg-white pl-12 pr-10 text-sm font-medium text-zinc-700 shadow-sm transition focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200">
                        <option value="">{{ __('Todos los municipios') }}</option>
                        @foreach ($municipalities as $municipality)
                            <option value="{{ $municipality->slug }}" @selected($selectedMunicipality?->id === $municipality->id)>{{ $municipality->name }}</option>
                        @endforeach
                    </select>
                    <flux:icon.chevron-down class="pointer-events-none absolute right-4 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
                </label>

                <label class="relative block">
                    <span class="sr-only">{{ __('Filtrar por categoría') }}</span>
                    <flux:icon.squares-2x2 class="pointer-events-none absolute left-4 top-1/2 z-10 size-5 -translate-y-1/2 text-brand-600" variant="outline" />
                    <select name="categoria" class="h-12 w-full appearance-none rounded-xl border border-zinc-200 bg-white pl-12 pr-10 text-sm font-medium text-zinc-700 shadow-sm transition focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200">
                        <option value="">{{ __('Todas las categorías') }}</option>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected($selectedCategory === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <flux:icon.chevron-down class="pointer-events-none absolute right-4 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
                </label>

                <label class="relative block">
                    <span class="sr-only">{{ __('Filtrar por fecha') }}</span>
                    <flux:icon.calendar-days class="pointer-events-none absolute left-4 top-1/2 z-10 size-5 -translate-y-1/2 text-brand-600" variant="outline" />
                    <input type="date" name="fecha" value="{{ $selectedDate }}" class="h-12 w-full rounded-xl border border-zinc-200 bg-white pl-12 pr-3 text-sm font-medium text-zinc-700 shadow-sm transition focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-200">
                </label>

                <flux:button type="submit" variant="primary" icon="magnifying-glass" class="h-12 justify-center px-8">
                    {{ __('Buscar') }}
                </flux:button>
            </form>
        </div>
    </section>

    <main class="mx-auto max-w-7xl px-4 pb-12 pt-24 sm:px-6 lg:px-8">
        <div class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-white sm:text-3xl">
                    {{ __('Eventos') }} <span class="text-brand-600">{{ __('destacados') }}</span>
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Vive, apoya y descubre lo mejor de los negocios locales.') }}</p>
            </div>

            @if ($selectedMunicipality || $selectedCategory || $selectedDate)
                <flux:button :href="route('eventos')" variant="filled" size="sm" icon-trailing="arrow-right" wire:navigate>
                    {{ __('Ver todos los eventos') }}
                </flux:button>
            @endif
        </div>

        @if ($events->isEmpty())
            <x-states.empty
                :title="__('Todavía no hay eventos publicados')"
                :description="__('Vuelve pronto — aquí aparecerán los eventos de los negocios de tu comunidad.')"
            />
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($events as $event)
                    <a href="{{ route('eventos.show', $event) }}" wire:navigate class="group overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="relative aspect-[16/10] w-full overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                            @if ($event->coverUrl())
                                <img src="{{ $event->coverUrl() }}" alt="{{ $event->title }}" class="size-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async">
                            @else
                                <div class="flex size-full items-center justify-center bg-gradient-to-br from-brand-50 to-amber-50 dark:from-brand-950 dark:to-zinc-900">
                                    <flux:icon.calendar-days class="size-12 text-brand-300 dark:text-brand-800" variant="outline" />
                                </div>
                            @endif

                            <span class="absolute left-3 top-3 flex min-w-12 flex-col items-center rounded-xl bg-brand-600 px-2.5 py-2 text-white shadow-lg">
                                <span class="text-[10px] font-bold uppercase leading-none">{{ $event->starts_at->translatedFormat('D') }}</span>
                                <span class="mt-1 text-xl font-black leading-none">{{ $event->starts_at->format('d') }}</span>
                                <span class="mt-0.5 text-[10px] font-bold uppercase leading-none">{{ $event->starts_at->translatedFormat('M') }}</span>
                            </span>

                            @if ($event->categoryLabel())
                                <span class="absolute -bottom-px left-3 inline-flex items-center gap-1.5 rounded-t-xl bg-white px-3 py-2 text-xs font-semibold text-brand-700 shadow-sm dark:bg-zinc-900 dark:text-brand-300">
                                    <flux:icon :name="$categoryIcons[$event->category] ?? 'calendar-days'" class="size-4" variant="outline" />
                                    {{ $event->categoryLabel() }}
                                </span>
                            @endif
                        </div>

                        <div class="p-4">
                            @if ($event->municipality)
                                <p class="mb-1.5 flex items-center gap-1 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    <flux:icon.map-pin class="size-3.5" variant="outline" />
                                    {{ $event->municipality->name }}
                                </p>
                            @endif
                            <h3 class="line-clamp-2 min-h-12 text-base font-bold leading-6 text-zinc-950 transition group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">{{ $event->title }}</h3>
                            @if ($event->business)
                                <p class="mt-1 line-clamp-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $event->business->name }}</p>
                            @endif
                            <p class="mt-3 flex items-start gap-1.5 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                                <flux:icon.calendar-days class="mt-0.5 size-3.5 shrink-0" variant="outline" />
                                {{ $event->starts_at->translatedFormat('l j \\d\\e F, Y · g:i a') }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $events->links() }}
            </div>
        @endif
    </main>
</x-layouts::cliente>
