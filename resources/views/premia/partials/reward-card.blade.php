@php
    $photoUrl = $reward->imageUrl() ?? $reward->product?->primaryImage()?->url();
    $available = $reward->stockAvailable();
@endphp

<a href="{{ route('premia.show', $reward) }}" wire:navigate class="group block overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
    <div class="relative aspect-[4/3] overflow-hidden bg-zinc-100 dark:bg-zinc-800">
        @if ($photoUrl)
            <img src="{{ $photoUrl }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]" alt="{{ $reward->title }}" loading="lazy" decoding="async">
        @else
            <div class="flex h-full w-full items-center justify-center">
                <flux:icon.gift class="size-10 text-zinc-300 dark:text-zinc-700" variant="outline" />
            </div>
        @endif

        @if ($available === 0)
            <span class="absolute left-3 top-3 inline-flex rounded-full bg-rose-600 px-2.5 py-1 text-xs font-semibold text-white">{{ __('Agotado') }}</span>
        @endif
    </div>

    <div class="space-y-1.5 p-4">
        <h3 class="line-clamp-2 text-base font-semibold text-zinc-950 dark:text-white">{{ $reward->title }}</h3>
        <p class="line-clamp-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ $reward->business->name }}
            @if ($reward->business->municipality)
                · {{ $reward->business->municipality->name }}
            @endif
        </p>

        <div class="flex items-center justify-between pt-1">
            <span class="inline-flex items-center gap-1 text-sm font-semibold text-brand-600 dark:text-brand-300">
                <flux:icon.sparkles class="size-4" variant="solid" />
                {{ __(':points Merkapuntos', ['points' => $reward->points_cost]) }}
            </span>

            @if ($available !== null && $available > 0)
                <span class="text-xs text-zinc-400">{{ __(':n disponibles', ['n' => $available]) }}</span>
            @endif
        </div>
    </div>
</a>
