@php
    $business = $product->business;
    $photo = $product->media->first();
@endphp

<section class="mb-4 rounded-2xl border border-brand-200 bg-white p-4 shadow-sm dark:border-brand-900 dark:bg-zinc-900">
    <div class="mb-3 flex items-center justify-between gap-3">
        <flux:badge color="red" icon="megaphone">{{ __('Patrocinado') }}</flux:badge>
        <span class="text-xs text-zinc-500">{{ $promotion->municipality?->name ?? __('Tu zona') }}</span>
    </div>

    <div class="flex items-center gap-4">
        <div class="size-24 shrink-0 overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
            @if ($photo)
                <img src="{{ $photo->url() }}" alt="{{ $product->name }}" class="size-full object-cover">
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-xs text-zinc-500">{{ $business->name }}</p>
            <flux:heading>{{ $product->name }}</flux:heading>
            @if ($product->price)
                <p class="mt-1 font-bold text-brand-600">${{ number_format((float) $product->price, 0, ',', '.') }}</p>
            @endif
        </div>
        <flux:button variant="primary" :href="route('promotions.click', $promotion)">
            {{ __('Ver producto') }}
        </flux:button>
    </div>
</section>
