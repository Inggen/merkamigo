<x-layouts::app :title="__('Inicio')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <flux:heading size="xl">{{ __('Hola, :name', ['name' => auth()->user()->name]) }}</flux:heading>

        @if (! $municipality)
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                {{ __('Elige tu municipio para ver negocios cercanos.') }}
            </flux:text>

            <div class="flex flex-wrap gap-2">
                @foreach ($municipalities as $option)
                    <form method="POST" action="{{ route('clientes.municipio') }}">
                        @csrf
                        <input type="hidden" name="municipality_id" value="{{ $option->id }}">
                        <flux:button type="submit" variant="ghost">{{ $option->name }}</flux:button>
                    </form>
                @endforeach
            </div>
        @else
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ __('Mostrando :municipio.', ['municipio' => $municipality->name]) }}
                </flux:text>

                <flux:dropdown>
                    <flux:button size="sm" variant="ghost" icon-trailing="chevron-down">{{ __('Cambiar municipio') }}</flux:button>
                    <flux:menu>
                        @foreach ($municipalities as $option)
                            <form method="POST" action="{{ route('clientes.municipio') }}">
                                @csrf
                                <input type="hidden" name="municipality_id" value="{{ $option->id }}">
                                <flux:menu.item as="button" type="submit">{{ $option->name }}</flux:menu.item>
                            </form>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
            </div>

            <form method="GET" action="{{ route('buscar') }}" class="flex gap-2">
                <input type="hidden" name="municipio" value="{{ $municipality->id }}">
                <flux:input name="q" placeholder="{{ __('Buscar negocios o productos...') }}" class="flex-1" />
                <flux:button type="submit" variant="primary">{{ __('Buscar') }}</flux:button>
            </form>

            @if ($categories->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach ($categories as $category)
                        <flux:button size="sm" variant="ghost" :href="route('plaza.category', [$municipality, $category])" wire:navigate>
                            {{ $category->name }}
                        </flux:button>
                    @endforeach
                </div>
            @endif

            <div class="mt-2 flex items-center justify-between">
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
                        @include('plaza.partials.business-card')
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</x-layouts::app>
