@php
    $pageTitle = __('En vivo');
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('En vivo')],
        ]),
    ];
@endphp

<x-layouts::cliente
    :title="$pageTitle"
    :description="__('Transmisiones en vivo de negocios locales en Merkamigo: en vivo ahora, próximas y replays recientes.')"
    :canonical="route('live.index')"
    :schema-graph="$schemaGraph"
>
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        <h1 class="mb-6 text-2xl font-semibold tracking-tight text-carbon dark:text-white">{{ __('En vivo') }}</h1>

        @if ($live->isEmpty() && $upcoming->isEmpty() && $replays->isEmpty())
            <x-states.empty
                :title="__('Todavía no hay transmisiones en vivo')"
                :description="__('Vuelve pronto — aquí aparecerán las transmisiones en vivo de los negocios de tu comunidad.')"
            />
        @else
            @if ($live->isNotEmpty())
                <section class="mb-10">
                    <flux:heading size="lg" class="mb-4">{{ __('En vivo ahora') }}</flux:heading>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($live as $stream)
                            <a href="{{ route('live.show', $stream) }}" wire:navigate class="group overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:border-brand-300 dark:border-zinc-800 dark:bg-zinc-900">
                                <div class="relative aspect-video w-full bg-zinc-900">
                                    @if ($stream->coverUrl())
                                        <img src="{{ $stream->coverUrl() }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                    @endif
                                    <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">
                                        <span class="size-1.5 rounded-full bg-white"></span>
                                        {{ __('EN VIVO') }}
                                    </span>
                                </div>
                                <div class="p-3">
                                    <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $stream->title }}</p>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $stream->business->name }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($upcoming->isNotEmpty())
                <section class="mb-10">
                    <flux:heading size="lg" class="mb-4">{{ __('Próximos eventos') }}</flux:heading>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($upcoming as $stream)
                            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                <div class="relative aspect-video w-full bg-zinc-100 dark:bg-zinc-800">
                                    @if ($stream->coverUrl())
                                        <img src="{{ $stream->coverUrl() }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                    @endif
                                </div>
                                <div class="p-3">
                                    <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $stream->title }}</p>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $stream->business->name }} · {{ $stream->scheduled_at->translatedFormat('d \d\e F, h:mm a') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($replays->isNotEmpty())
                <section>
                    <flux:heading size="lg" class="mb-4">{{ __('Replays recientes') }}</flux:heading>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($replays as $stream)
                            <a href="{{ route('live.show', $stream) }}" wire:navigate class="group overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:border-brand-300 dark:border-zinc-800 dark:bg-zinc-900">
                                <div class="relative aspect-video w-full bg-zinc-900">
                                    @if ($stream->coverUrl())
                                        <img src="{{ $stream->coverUrl() }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                    @endif
                                    <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-zinc-900/80 px-2 py-0.5 text-xs font-semibold text-white">
                                        {{ __('REPLAY') }}
                                    </span>
                                </div>
                                <div class="p-3">
                                    <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $stream->title }}</p>
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $stream->business->name }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
</x-layouts::cliente>
