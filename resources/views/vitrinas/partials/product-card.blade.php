@php $photo = $product->media->first(); @endphp

<div class="group relative overflow-hidden rounded-[24px] border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
    <a href="{{ route('vitrinas.product', [$business, $product]) }}" class="block" wire:navigate>
        <div class="relative aspect-[4/3] overflow-hidden bg-zinc-100 dark:bg-zinc-800">
            @if ($photo)
                <img src="{{ $photo->url() }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]" alt="{{ $photo->alt_text ?? __('Imagen de :product en :business', ['product' => $product->name, 'business' => $business->name]) }}" loading="lazy" decoding="async">
            @endif

            @if ($product->isSoldOut())
                <span class="absolute left-3 top-3 inline-flex rounded-full bg-rose-600 px-2.5 py-1 text-xs font-semibold text-white">{{ __('Agotado') }}</span>
            @elseif ($product->hasActivePromo())
                <span class="absolute left-3 top-3 inline-flex rounded-full bg-brand-600 px-2.5 py-1 text-xs font-semibold text-white">{{ __('Promo') }}</span>
            @endif
        </div>

        <div class="space-y-2 p-4">
            <h3 class="line-clamp-2 text-base font-semibold text-zinc-950 dark:text-white">{{ $product->name }}</h3>

            @if ($product->description)
                <p class="line-clamp-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ $product->description }}</p>
            @endif

            <div class="flex items-end justify-between gap-3">
                <div class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    @if ($product->hasActivePromo())
                        <div class="text-xs text-zinc-400 line-through">${{ number_format((float) $product->price, 0, ',', '.') }}</div>
                        <div class="text-base font-semibold text-brand-600 dark:text-brand-300">${{ number_format((float) $product->promo_price, 0, ',', '.') }}</div>
                    @elseif ($product->price_type === 'exacto' && $product->price)
                        <div class="text-base font-semibold">${{ number_format((float) $product->price, 0, ',', '.') }}</div>
                    @elseif ($product->price_type === 'desde' && $product->price)
                        <div class="text-base font-semibold">{{ __('Desde') }} ${{ number_format((float) $product->price, 0, ',', '.') }}</div>
                    @elseif ($product->price_type === 'consultar')
                        <div class="text-base font-semibold">{{ __('Consultar') }}</div>
                    @endif
                </div>

                <span class="text-xs font-medium text-zinc-400 transition group-hover:text-brand-600 dark:group-hover:text-brand-300">{{ __('Ver más') }}</span>
            </div>
        </div>
    </a>

    <div class="absolute top-3 right-3">
        <livewire:favorite-button :favoritable="$product" :compact="true" :key="'product-card-'.$product->id" />
    </div>
</div>
