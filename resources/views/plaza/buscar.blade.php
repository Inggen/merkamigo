<x-layouts::public :title="__('Buscar')">
    <div class="mx-auto max-w-5xl px-6 py-10">
        <flux:heading size="xl" class="mb-6">{{ __('Buscar negocios') }}</flux:heading>

        <form method="GET" action="{{ route('buscar') }}" class="mb-8 flex flex-col gap-3 sm:flex-row">
            <flux:input name="q" value="{{ $query }}" placeholder="{{ __('Nombre del negocio...') }}" class="flex-1" />

            <flux:select name="municipio">
                <flux:select.option value="">{{ __('Todos los municipios') }}</flux:select.option>
                @foreach ($municipalities as $municipality)
                    <flux:select.option value="{{ $municipality->id }}" :selected="request('municipio') == $municipality->id">
                        {{ $municipality->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:button type="submit" variant="primary">{{ __('Buscar') }}</flux:button>
        </form>

        @if ($businesses->isEmpty())
            <x-states.empty title="{{ __('No encontramos resultados') }}" description="{{ __('Intenta con otro nombre o cambia el municipio.') }}" />
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($businesses as $business)
                    @include('plaza.partials.business-card')
                @endforeach
            </div>

            <div class="mt-8">
                {{ $businesses->links() }}
            </div>
        @endif
    </div>
</x-layouts::public>
