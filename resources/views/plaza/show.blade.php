<x-layouts::public :title="__('Plaza de :municipio', ['municipio' => $municipio->name])">
    <div class="mx-auto max-w-5xl px-6 py-10">
        <flux:heading size="xl">{{ __('Plaza de :municipio', ['municipio' => $municipio->name]) }}</flux:heading>
        <flux:subheading class="mb-6">{{ __('Apoya lo local, impulsa tu comunidad.') }}</flux:subheading>

        <div class="mb-6 flex flex-wrap gap-2">
            <flux:button size="sm" :variant="$category ? 'ghost' : 'primary'" :href="route('plaza.show', $municipio)" wire:navigate>
                {{ __('Todas') }}
            </flux:button>
            @foreach ($categories as $cat)
                <flux:button
                    size="sm"
                    :variant="$category?->id === $cat->id ? 'primary' : 'ghost'"
                    :href="route('plaza.category', [$municipio, $cat])"
                    wire:navigate
                >
                    {{ $cat->name }}
                </flux:button>
            @endforeach
        </div>

        @if ($businesses->isEmpty())
            <x-states.empty
                title="{{ __('Todavía no hay negocios publicados aquí') }}"
                description="{{ __('Vuelve pronto — cada semana se suman más emprendedores de :municipio.', ['municipio' => $municipio->name]) }}"
            />
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
