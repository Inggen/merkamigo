@php
    $photoUrl = $reward->imageUrl() ?? $reward->product?->primaryImage()?->url();
    $accountRow = $accounts->first(fn ($row) => $row->business->id === $reward->business_id);
    $availablePoints = $accountRow?->available ?? 0;
    $canAfford = $accountRow && $availablePoints >= $reward->points_cost && $reward->isRedeemable();
    $missingPoints = max(0, $reward->points_cost - $availablePoints);
@endphp

<article class="group flex min-w-0 flex-col overflow-hidden rounded-3xl border border-zinc-200/90 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-brand-300 hover:shadow-xl hover:shadow-brand-950/10 dark:border-white/10 dark:bg-zinc-900 dark:hover:border-brand-500/40 dark:hover:shadow-black/30">
    <a href="{{ route('premia.show', $reward) }}" wire:navigate class="relative block aspect-[16/9] overflow-hidden bg-gradient-to-br from-brand-100 to-orange-50 dark:from-brand-950 dark:to-zinc-900">
        @if ($photoUrl)
            <img src="{{ $photoUrl }}" class="size-full object-cover transition duration-500 group-hover:scale-105" alt="{{ $reward->title }}" loading="lazy" decoding="async">
        @else
            <div class="flex size-full items-center justify-center">
                <span class="inline-flex size-16 items-center justify-center rounded-full bg-white/80 text-brand-600 shadow-sm backdrop-blur dark:bg-white/10 dark:text-brand-300">
                    <flux:icon.gift class="size-8" variant="outline" />
                </span>
            </div>
        @endif

        <span class="absolute bottom-3 left-3 inline-flex max-w-[calc(100%-1.5rem)] items-center rounded-full bg-zinc-950/80 px-3 py-1.5 text-xs font-semibold text-white shadow-sm backdrop-blur">
            <span class="truncate">{{ $reward->business->name }}</span>
            @if ($reward->business->municipality)
                <span class="shrink-0">&nbsp;· {{ $reward->business->municipality->name }}</span>
            @endif
        </span>
    </a>

    <div class="flex flex-1 flex-col p-5">
        <h3 class="line-clamp-2 text-lg font-bold tracking-tight text-zinc-950 dark:text-white">{{ $reward->title }}</h3>

        @if ($reward->description)
            <p class="mt-1 line-clamp-2 text-sm leading-5 text-zinc-500 dark:text-zinc-400">{{ $reward->description }}</p>
        @endif

        <div class="mt-3 flex items-baseline gap-2">
            <span class="text-2xl font-black text-brand-600 dark:text-brand-400">{{ number_format($reward->points_cost, 0, ',', '.') }}</span>
            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Merkapuntos') }}</span>
        </div>

        @if ($accountRow && ! $canAfford)
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Te faltan :points para canjearla.', ['points' => number_format($missingPoints, 0, ',', '.')]) }}</p>
        @elseif (! $accountRow)
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Acumula en este negocio para canjearla.') }}</p>
        @endif

        <div class="mt-auto flex items-end justify-between gap-3 pt-5">
            <a href="{{ route('premia.show', $reward) }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-medium text-zinc-500 underline decoration-zinc-300 underline-offset-4 transition hover:text-brand-600 dark:text-zinc-400 dark:decoration-zinc-700 dark:hover:text-brand-300">
                <flux:icon.document-text class="size-4" variant="outline" />
                {{ __('Ver condiciones') }}
            </a>

            @if ($canAfford)
                <flux:button size="sm" variant="primary" wire:click="reserve({{ $reward->id }})">
                    {{ __('Usar :points Merkapuntos', ['points' => $reward->points_cost]) }}
                </flux:button>
            @else
                <flux:button size="sm" :href="route('premia.show', $reward)" variant="primary" wire:navigate>
                    {{ __('Quiero esta recompensa') }}
                </flux:button>
            @endif
        </div>
    </div>
</article>
