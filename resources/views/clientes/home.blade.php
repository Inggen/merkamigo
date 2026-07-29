<x-layouts::cliente :title="__('Inicio')">
    @if (! $municipality)
        <div class="bg-gradient-to-br from-brand-700 to-brand-900 px-6 py-16 text-white">
            <div class="mx-auto max-w-3xl text-center">
                <flux:heading size="xl" class="text-3xl text-white sm:text-4xl">{{ __('Descubre lo local, conecta con tu comunidad') }}</flux:heading>
                <flux:text class="mt-2 mb-8 text-brand-100">
                    {{ __('Elige tu municipio para ver negocios cercanos.') }}
                </flux:text>

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
            </div>
        </div>
    @else
        <div class="bg-gradient-to-br from-brand-700 to-brand-900 px-6 py-12 text-white sm:py-14">
            <div class="mx-auto max-w-6xl">
                <flux:heading size="xl" class="text-3xl text-white sm:text-4xl">{{ __('Descubre lo local, conecta con tu comunidad') }}</flux:heading>
                <flux:text class="mt-2 max-w-xl text-brand-100">
                    {{ __('Mostrando :municipio. Apoya negocios de tu área y encuentra lo que necesitas, cerca de ti.', ['municipio' => $municipality->name]) }}
                </flux:text>

                <div class="mt-6 flex flex-col gap-2 rounded-2xl bg-white p-2 shadow-lg sm:flex-row sm:items-center dark:bg-zinc-800">
                    <form method="GET" action="{{ route('buscar') }}" class="flex flex-1 items-center gap-2 px-2">
                        <flux:icon.magnifying-glass class="size-5 shrink-0 text-zinc-400" variant="outline" />
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="{{ __('Buscar negocios, productos o servicios...') }}"
                            class="w-full border-0 bg-transparent py-2.5 text-sm text-carbon placeholder:text-zinc-400 focus:outline-none dark:text-white"
                        >
                        <input type="hidden" name="municipio" value="{{ $municipality->id }}">
                        <button type="submit" class="shrink-0 rounded-xl bg-brand-600 p-2.5 text-white transition hover:bg-brand-700">
                            <flux:icon.magnifying-glass class="size-4" variant="outline" />
                        </button>
                    </form>

                    <flux:dropdown class="shrink-0 border-t border-zinc-100 pt-2 sm:border-t-0 sm:border-l sm:pt-0 sm:pl-2 dark:border-zinc-700">
                        <flux:button variant="ghost" icon="map-pin" icon-trailing="chevron-down" class="w-full justify-start sm:w-auto">
                            {{ $municipality->name }}
                        </flux:button>
                        <flux:menu>
                            @foreach ($municipalities as $option)
                                <form method="POST" action="{{ route('clientes.municipio') }}">
                                    @csrf
                                    <input type="hidden" name="municipality_id" value="{{ $option->id }}">
                                    <flux:menu.item as="button" type="submit" :icon="$municipality->id === $option->id ? 'check' : null" class="w-full cursor-pointer">
                                        {{ $option->name }}
                                    </flux:menu.item>
                                </form>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>

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
