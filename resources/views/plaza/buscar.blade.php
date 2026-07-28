<x-layouts::cliente :title="__('Buscar')">
    <div class="mx-auto max-w-6xl px-6 py-8">
        <flux:heading size="xl" class="mb-1">{{ __('Resultados de búsqueda') }}</flux:heading>
        <flux:subheading class="mb-6">
            @if ($query !== '')
                {{ __('Se encontraron :count resultados para ":query".', ['count' => $businesses->total(), 'query' => $query]) }}
            @else
                {{ __('Filtra por categoría o municipio, o escribe qué estás buscando.') }}
            @endif
        </flux:subheading>

        <div class="grid gap-8 lg:grid-cols-[16rem_1fr]">
            <aside class="space-y-6">
                <form method="GET" action="{{ route('buscar') }}" class="space-y-4">
                    <flux:input name="q" value="{{ $query }}" placeholder="{{ __('Nombre, producto o servicio...') }}" icon="magnifying-glass" />

                    <flux:select name="categoria" :label="__('Categoría')">
                        <flux:select.option value="">{{ __('Todas') }}</flux:select.option>
                        @foreach ($categories as $category)
                            <flux:select.option value="{{ $category->id }}" :selected="request('categoria') == $category->id">
                                {{ $category->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select name="municipio" :label="__('Municipio')">
                        <flux:select.option value="">{{ __('Todos') }}</flux:select.option>
                        @foreach ($municipalities as $municipality)
                            <flux:select.option value="{{ $municipality->id }}" :selected="request('municipio') == $municipality->id">
                                {{ $municipality->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:button type="submit" variant="primary" class="w-full">{{ __('Aplicar filtros') }}</flux:button>
                </form>
            </aside>

            <div>
                @if ($businesses->isEmpty())
                    <x-states.empty title="{{ __('No encontramos resultados') }}" description="{{ __('Intenta con otro nombre, categoría o municipio.') }}" />
                @else
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($businesses as $business)
                            <x-business-card :business="$business" />
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $businesses->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::cliente>
