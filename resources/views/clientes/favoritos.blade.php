<x-layouts::app :title="__('Favoritos')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <flux:heading size="xl">{{ __('Favoritos') }}</flux:heading>

        @if ($businesses->isEmpty() && $products->isEmpty())
            <x-states.empty
                title="{{ __('Todavía no tienes favoritos') }}"
                description="{{ __('Guarda negocios o productos desde su página para encontrarlos rápido aquí.') }}"
            />
        @else
            @if ($businesses->isNotEmpty())
                <div>
                    <flux:subheading class="mb-3">{{ __('Negocios') }}</flux:subheading>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($businesses as $business)
                            @include('plaza.partials.business-card')
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($products->isNotEmpty())
                <div>
                    <flux:subheading class="mb-3">{{ __('Productos') }}</flux:subheading>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($products as $product)
                            @include('vitrinas.partials.product-card', ['business' => $product->business, 'product' => $product])
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-layouts::app>
