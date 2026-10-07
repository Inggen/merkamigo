@php
    $pageTitle = __('Merkapuntos: recompensas por apoyar negocios locales');
    $pageDescription = __('Descubre qué puedes canjear en los negocios de tu municipio con Merkamigo Premia — sin necesidad de registrarte.');
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Merkapuntos')],
        ]),
    ];
@endphp

<x-layouts::cliente
    :title="$pageTitle"
    :description="$pageDescription"
    :canonical="route('premia.index')"
    :schema-graph="$schemaGraph"
>
    {{-- TODO_Merkapuntos.md, F2.2: catálogo público, navegable sin login.
         Estilo tomado de la referencia "Clientes y recompensas" (mockup) —
         banner explicativo + pasos + grilla filtrable. --}}
    <section
        class="relative isolate overflow-hidden border-b border-zinc-200 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-950"
        style="background-image: url('{{ asset('images/backgrounds/fondo_merkapuntos_premia.webp') }}'); background-position: center; background-size: cover;"
    >
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-white/95 via-white/70 to-black/20 dark:from-zinc-950/95 dark:via-zinc-950/70 dark:to-black/35"></div>

        <div class="mx-auto flex min-h-[300px] max-w-7xl items-center px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
            <div class="grid w-full items-center gap-8 lg:grid-cols-[1.05fr_1fr]">
                <div class="max-w-2xl">
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-950 dark:text-white sm:text-4xl">
                        {{ __('Apoyar lo local tiene recompensa') }}
                    </h1>
                    <p class="mt-3 max-w-xl text-base leading-7 text-zinc-700 dark:text-zinc-200">
                        {{ __('Descubre negocios adheridos a Merkamigo Premia, acumula Merkapuntos con tus compras verificadas y canjéalos por experiencias, productos y servicios únicos de tu comunidad.') }}
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        @guest
                            <flux:button :href="route('register')" variant="primary" wire:navigate>{{ __('Crear cuenta') }}</flux:button>
                            <flux:button :href="route('login')" variant="ghost" wire:navigate>{{ __('Iniciar sesión') }}</flux:button>
                        @else
                            <flux:button :href="route('merkapuntos')" variant="primary" wire:navigate>{{ __('Ver mis Merkapuntos') }}</flux:button>
                        @endguest
                    </div>
                </div>

                <div class="grid gap-3 text-center sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/70 bg-white/95 p-5 shadow-xl shadow-zinc-950/10 backdrop-blur dark:border-white/10 dark:bg-zinc-900/90">
                        <flux:icon.shopping-cart class="mx-auto mb-2 size-7 text-brand-600" variant="outline" />
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('1. Compra') }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ __('En negocios adheridos de tu municipio.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/70 bg-white/95 p-5 shadow-xl shadow-zinc-950/10 backdrop-blur dark:border-white/10 dark:bg-zinc-900/90">
                        <flux:icon.sparkles class="mx-auto mb-2 size-7 text-brand-600" variant="outline" />
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('2. Acumula') }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ __('Gana puntos con tus compras verificadas.') }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/70 bg-white/95 p-5 shadow-xl shadow-zinc-950/10 backdrop-blur dark:border-white/10 dark:bg-zinc-900/90">
                        <flux:icon.gift class="mx-auto mb-2 size-7 text-brand-600" variant="outline" />
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('3. Canjea') }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ __('Elige tu recompensa y disfrútala.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('premia.index') }}" class="mb-6 flex flex-wrap items-center gap-3">
            <flux:input type="search" name="q" value="{{ $query }}" :placeholder="__('Buscar recompensas o negocios...')" :aria-label="__('Buscar recompensas o negocios')" class="max-w-xs" />

            <select name="municipio" aria-label="{{ __('Filtrar por municipio') }}" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" onchange="this.form.submit()">
                <option value="">{{ __('Todos los municipios') }}</option>
                @foreach ($municipalities as $municipality)
                    <option value="{{ $municipality->slug }}" @selected($selectedMunicipality?->id === $municipality->id)>{{ $municipality->name }}</option>
                @endforeach
            </select>

            <select name="categoria" aria-label="{{ __('Filtrar por categoría') }}" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" onchange="this.form.submit()">
                <option value="">{{ __('Todas las categorías') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected($selectedCategory?->id === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>

            <flux:button type="submit" variant="ghost" size="sm">{{ __('Buscar') }}</flux:button>

            @if ($query !== '' || $selectedMunicipality || $selectedCategory)
                <flux:button :href="route('premia.index')" variant="ghost" size="sm" wire:navigate>{{ __('Limpiar filtros') }}</flux:button>
            @endif
        </form>

        @if ($rewards->isEmpty())
            <x-states.empty
                :title="__('Todavía no hay recompensas con estos filtros')"
                :description="__('Prueba con otro municipio o categoría, o vuelve pronto — cada semana se suman más negocios a Merkamigo Premia.')"
            />
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($rewards as $reward)
                    @include('premia.partials.reward-card', ['reward' => $reward])
                @endforeach
            </div>

            <div class="mt-8">
                {{ $rewards->links() }}
            </div>
        @endif
    </div>
</x-layouts::cliente>
