@php
    $event = $post->publicEvent;
    $business = $post->business;
    $location = $event->location_text ?: ($event->municipality?->name ?? $business->municipality?->name);
    $categoryIcons = [
        'musica' => 'musical-note',
        'taller' => 'user-group',
        'mercado' => 'shopping-bag',
        'gastronomia' => 'sparkles',
        'arte' => 'paint-brush',
        'deporte' => 'trophy',
        'otro' => 'calendar-days',
    ];
    $categoryIcon = $categoryIcons[$event->category] ?? 'calendar-days';
@endphp

<article data-event-post-card class="overflow-hidden rounded-[1.75rem] border border-zinc-200 bg-white p-4 shadow-sm sm:p-5 dark:border-zinc-800 dark:bg-zinc-900">
    <header class="grid items-center gap-4 sm:grid-cols-[minmax(0,1fr)_auto_auto]">
        <a href="{{ route('vitrinas.show', $business) }}" wire:navigate class="flex min-w-0 items-center gap-3">
            <div class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-full border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                @if ($business->logoUrl())
                    <img src="{{ $business->logoUrl() }}" class="size-full object-cover" alt="{{ $business->name }}" loading="lazy">
                @else
                    <flux:icon.building-storefront class="size-5 text-zinc-400" variant="outline" />
                @endif
            </div>
            <div class="min-w-0">
                <p class="truncate text-base font-bold text-zinc-950 sm:text-lg dark:text-white">{{ $business->name }}</p>
                <p class="flex items-center gap-1.5 truncate text-sm text-zinc-500 dark:text-zinc-400">
                    <flux:icon.map-pin class="size-4 shrink-0" variant="solid" />
                    {{ $business->municipality?->name }} · {{ $post->published_at?->diffForHumans() }}
                </p>
            </div>
        </a>

        <div class="flex flex-wrap items-center justify-start gap-2 sm:justify-center">
            <span class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm">
                <flux:icon.calendar-days class="size-4" />
                {{ __('Evento') }}
            </span>
            <span class="inline-flex max-w-64 items-center gap-2 rounded-full bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                <flux:icon :name="$categoryIcon" class="size-4 shrink-0" variant="solid" />
                <span class="truncate">{{ $event->categoryLabel() ?: __('Evento local') }} / {{ $event->title }}</span>
            </span>
        </div>

        <div class="flex items-center justify-end gap-2">
            <x-owner-edit-link
                :business="$business"
                :href="route('emprendedores.negocios.eventos', $business).'#evento-'.$event->id"
                :label="__('Editar evento')"
                compact
            />
            <livewire:follow-button :business="$business" compact :key="'follow-'.$business->id.'-event-post-'.$post->id" />
        </div>
    </header>

    <a href="{{ route('eventos.show', $event) }}" wire:navigate class="group relative mt-4 block min-h-72 overflow-hidden rounded-2xl bg-zinc-950 sm:min-h-80">
        @if ($event->coverUrl())
            <img src="{{ $event->coverUrl() }}" alt="{{ $event->title }}" class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-[1.02]" loading="lazy">
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-brand-950 via-brand-700 to-amber-600"></div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-r from-black/85 via-black/35 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-transparent to-transparent"></div>
        <div class="absolute inset-x-0 bottom-0 max-w-2xl p-6 text-white sm:p-8">
            <h2 class="text-3xl font-black tracking-tight sm:text-5xl">{{ $event->title }}</h2>
            @if ($event->description)
                <p class="mt-2 line-clamp-2 text-base font-medium text-white/90 sm:text-xl">{{ $event->description }}</p>
            @endif
        </div>
    </a>

    <div class="mt-3 grid gap-3 rounded-2xl bg-brand-50/60 p-4 sm:grid-cols-2 dark:bg-brand-500/10">
        <div class="flex items-center gap-3 sm:border-r sm:border-brand-200 sm:pr-4 dark:sm:border-brand-500/20">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                <flux:icon.calendar-days class="size-6" variant="outline" />
            </span>
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wide text-zinc-500">{{ __('Fecha y hora') }}</p>
                <p class="truncate font-bold text-zinc-950 dark:text-white">{{ $event->starts_at->translatedFormat('l j \\d\\e F, g:i a') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 sm:pl-4">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand-100 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                <flux:icon.map-pin class="size-6" variant="outline" />
            </span>
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wide text-zinc-500">{{ __('Lugar') }}</p>
                <p class="truncate font-bold text-zinc-950 dark:text-white">{{ $location ?: __('Por confirmar') }}</p>
            </div>
        </div>
    </div>

    <a href="{{ route('eventos.show', $event) }}" wire:navigate class="mt-3 flex items-center gap-3 rounded-2xl border border-zinc-200 p-3 transition hover:border-brand-300 dark:border-zinc-700">
        <div class="size-14 shrink-0 overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-800">
            @if ($event->coverUrl())
                <img src="{{ $event->coverUrl() }}" class="size-full object-cover" alt="" loading="lazy">
            @else
                <div class="flex size-full items-center justify-center"><flux:icon.calendar-days class="size-6 text-zinc-400" /></div>
            @endif
        </div>
        <div class="min-w-0">
            <p class="truncate font-bold text-zinc-950 dark:text-white">{{ $event->title }}</p>
            <p class="text-sm text-zinc-500">{{ $event->starts_at->translatedFormat('d M, g:i a') }}</p>
        </div>
        <span class="ml-auto inline-flex shrink-0 items-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-bold text-white transition group-hover:bg-brand-700">
            {{ __('Ver evento') }}
            <flux:icon.chevron-right class="size-4" />
        </span>
    </a>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-1 border-t border-zinc-100 pt-3 dark:border-zinc-800">
        <livewire:post-reaction-button :post="$post" :key="'reaction-event-'.$post->id" />
        <button
            type="button"
            x-data
            x-on:click="navigator.share ? navigator.share({ title: {{ Js::from($event->title) }}, url: {{ Js::from(route('eventos.show', $event)) }} }) : navigator.clipboard.writeText({{ Js::from(route('eventos.show', $event)) }}).then(() => $flux.toast({ text: {{ Js::from(__('Enlace copiado')) }} }))"
            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-sm font-medium text-zinc-500 transition hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
        >
            <flux:icon.share class="size-4" variant="outline" />
            {{ __('Compartir') }}
        </button>
        <livewire:post-comments :post="$post" :key="'comments-event-'.$post->id" />
        <livewire:favorite-button :favoritable="$post" :key="'save-event-'.$post->id" />
    </div>
</article>
