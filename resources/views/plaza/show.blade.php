<x-layouts::cliente :title="__('Plaza de :municipio', ['municipio' => $municipio->name])">
    <div class="mx-auto max-w-6xl px-6 py-8">
        <flux:heading size="xl">{{ __('Plaza de :municipio', ['municipio' => $municipio->name]) }}</flux:heading>
        <flux:subheading class="mb-6">{{ __('Apoya lo local, impulsa tu comunidad.') }}</flux:subheading>

        <div class="mb-8">
            <x-category-icons
                :categories="$categories"
                :active-category="$category"
                :all-url="route('plaza.show', $municipio)"
                :url-for="fn ($cat) => route('plaza.category', [$municipio, $cat])"
            />
        </div>

        @if ($businesses->isEmpty())
            <x-states.empty
                title="{{ __('Todavía no hay negocios publicados aquí') }}"
                description="{{ __('Vuelve pronto — cada semana se suman más emprendedores de :municipio.', ['municipio' => $municipio->name]) }}"
            />
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($businesses as $business)
                    <x-business-card :business="$business" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $businesses->links() }}
            </div>
        @endif
    </div>
</x-layouts::cliente>
