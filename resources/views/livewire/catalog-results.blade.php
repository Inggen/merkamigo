<div id="{{ $type }}-results" class="relative scroll-mt-24">
    <div
        wire:loading.flex
        class="absolute inset-0 z-10 items-start justify-center rounded-xl bg-mist/75 pt-20 backdrop-blur-[1px] dark:bg-zinc-900/75"
    >
        <flux:icon.arrow-path class="size-7 animate-spin text-brand-600" />
        <span class="sr-only">{{ __('Cargando resultados') }}</span>
    </div>

    <div wire:loading.class="opacity-50" class="grid gap-4 transition-opacity sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($results as $result)
            @if ($type === 'products')
                @include('vitrinas.partials.product-card', [
                    'business' => $result->business,
                    'product' => $result,
                    'showBusinessName' => true,
                ])
            @else
                <x-business-card :business="$result" />
            @endif
        @endforeach
    </div>

    @if ($results->hasPages())
        <div class="mt-8">
            {{ $results->links(data: ['scrollTo' => '#'.$type.'-results']) }}
        </div>
    @endif
</div>
