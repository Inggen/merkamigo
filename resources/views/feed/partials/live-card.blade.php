<section class="mb-4 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <div class="grid md:grid-cols-[minmax(0,1.2fr)_minmax(18rem,.8fr)]">
        <x-live.feed-preview :live="$live" />

        <div class="flex flex-col p-5">
            <div class="flex items-center gap-3">
                <div class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-full border border-zinc-200 dark:border-zinc-700">
                    @if ($live->business->logoUrl())
                        <img src="{{ $live->business->logoUrl() }}" alt="{{ $live->business->name }}" class="size-full object-cover">
                    @else
                        <flux:icon.building-storefront class="size-5 text-zinc-400" />
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ $live->business->name }}</p>
                    <p class="text-xs text-zinc-500">{{ $live->business->municipality?->name }} · <span class="text-red-600">{{ __('En vivo ahora') }}</span></p>
                </div>
            </div>

            <flux:heading size="lg" class="mt-4">{{ $live->title }}</flux:heading>
            @if ($live->description)
                <flux:text class="mt-1 line-clamp-2 text-sm">{{ $live->description }}</flux:text>
            @endif

            @if ($live->pinnedProduct)
                @php $product = $live->pinnedProduct; $photo = $product->media->first(); @endphp
                <div class="mt-auto flex items-center gap-3 rounded-xl border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        @if ($photo)
                            <img src="{{ $photo->url() }}" alt="{{ $product->name }}" class="size-full object-cover">
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ $product->name }}</p>
                        @if ($product->price)
                            <p class="text-sm font-bold text-brand-600">${{ number_format((float) $product->price, 0, ',', '.') }}</p>
                        @endif
                    </div>
                    <flux:button size="sm" variant="primary" :href="route('live.show', $live)" wire:navigate>{{ __('Ver Live') }}</flux:button>
                </div>
            @endif
        </div>
    </div>
</section>
