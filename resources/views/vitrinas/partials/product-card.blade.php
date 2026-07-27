@php $photo = $product->media->first(); @endphp

<a href="{{ route('vitrinas.product', [$business, $product]) }}" class="block overflow-hidden rounded-xl border border-zinc-200 hover:border-brand-300 dark:border-zinc-700" wire:navigate>
    <div class="aspect-square bg-zinc-100 dark:bg-zinc-800">
        @if ($photo)
            <img src="{{ $photo->url() }}" class="h-full w-full object-cover" alt="{{ $product->name }}">
        @endif
    </div>
    <div class="p-2">
        <div class="truncate text-sm font-medium">{{ $product->name }}</div>
        <div class="text-sm text-zinc-500">
            @if ($product->price_type === 'exacto' && $product->price)
                ${{ number_format((float) $product->price, 0, ',', '.') }}
            @elseif ($product->price_type === 'desde' && $product->price)
                {{ __('Desde') }} ${{ number_format((float) $product->price, 0, ',', '.') }}
            @elseif ($product->price_type === 'consultar')
                {{ __('Consultar') }}
            @endif
        </div>
    </div>
</a>
