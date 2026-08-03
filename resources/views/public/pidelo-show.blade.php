<?php

use App\Domain\Needs\Models\Need;

$statusLabel = match ($need->status) {
    Need::PUBLICADA => __('Publicada'),
    Need::RECIBIENDO_OFERTAS => __('Recibiendo propuestas'),
    Need::SELECCIONADA => __('Seleccionada'),
    default => $need->status,
};

$statusClasses = match ($need->status) {
    Need::SELECCIONADA => 'bg-sky-100 text-sky-700',
    default => 'bg-emerald-100 text-emerald-700',
};

?>

<x-layouts::cliente :title="$need->title">
    <div class="mx-auto max-w-3xl px-6 py-8">
        <flux:link :href="route('pidelo')" wire:navigate class="text-sm">{{ __('← Volver a Pídelo') }}</flux:link>

        <div class="mt-4 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <flux:heading size="xl">{{ $need->title }}</flux:heading>
                <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-sm font-medium {{ $statusClasses }}">
                    {{ $statusLabel }}
                </span>
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-x-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                @if ($need->category)
                    <span>{{ $need->category->name }}</span>
                    <span>·</span>
                @endif
                @if ($need->municipality)
                    <span>{{ $need->municipality->name }}</span>
                    @if ($need->zone)
                        <span>· {{ $need->zone }}</span>
                    @endif
                    <span>·</span>
                @endif
                <span>{{ __('Publicada :fecha', ['fecha' => $need->published_at?->diffForHumans()]) }}</span>
            </div>

            @if ($need->budget)
                <span class="mt-4 inline-flex items-center rounded-full bg-rose-50 px-3 py-1.5 text-sm font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
                    {{ __('Presupuesto: :amount', ['amount' => '$'.number_format((float) $need->budget, 0, ',', '.')]) }}
                </span>
            @endif

            <flux:text class="mt-6 whitespace-pre-line text-zinc-600 dark:text-zinc-300">
                {{ $need->description }}
            </flux:text>

            @if ($need->media->isNotEmpty())
                <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($need->media as $media)
                        <img src="{{ $media->url() }}" class="aspect-square w-full rounded-xl object-cover" alt="{{ __('Foto de referencia de :title', ['title' => $need->title]) }}" loading="lazy" decoding="async">
                    @endforeach
                </div>
            @endif

            <div class="mt-6 flex items-center gap-2 border-t border-zinc-100 pt-6 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                <flux:icon.chat-bubble-left-right variant="outline" class="size-4" />
                <span>{{ trans_choice(':count propuesta recibida|:count propuestas recibidas', $need->offers_count, ['count' => $need->offers_count]) }}</span>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border border-brand-100 bg-brand-50 p-6 text-center dark:border-brand-900 dark:bg-brand-950">
            <flux:text class="text-zinc-600 dark:text-zinc-300">{{ __('¿También necesitas algo? Publica tu propia solicitud y recibe propuestas.') }}</flux:text>
            <flux:button variant="primary" :href="route('pidelo.nueva')" wire:navigate class="mt-3">
                {{ __('Publicar una solicitud') }}
            </flux:button>
        </div>
    </div>
</x-layouts::cliente>
