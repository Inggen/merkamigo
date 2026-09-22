@php
    $pageTitle = __('Reels');
@endphp

<x-layouts::cliente :title="$pageTitle" :description="__('Videos cortos de negocios locales en Merkamigo.')">
    <h1 class="sr-only">{{ __('Reels de Merkamigo') }}</h1>

    <div class="mx-auto max-w-md px-4 py-6 sm:px-6">
        @if ($reels->isEmpty())
            <x-states.empty
                :title="__('Todavía no hay reels')"
                :description="__('Vuelve pronto — los negocios podrán compartir videos cortos aquí.')"
            />
        @else
            {{-- Alcance reducido a propósito (Fase 4 del TODO social): scroll-snap
                 vertical dentro del layout normal del sitio, no un modo de
                 pantalla completa tipo TikTok — eso exigiría un layout aparte
                 sin header/footer que se deja para cuando haya volumen real. --}}
            <div class="space-y-6 sm:[scroll-snap-type:y_proximity]">
                @foreach ($reels as $reel)
                    @php $business = $reel->business; @endphp
                    <article id="reel-{{ $reel->id }}" class="scroll-mt-24 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900" style="scroll-snap-align: start;">
                        <div class="relative aspect-[9/16] w-full bg-black">
                            @php $video = $reel->media->first(); @endphp
                            @if ($video)
                                <x-media.video-player :src="$video->url()" aspect="reel" class="h-full rounded-none shadow-none" loop />
                            @endif

                            @if ($reel->products->isNotEmpty())
                                <a
                                    href="{{ route('vitrinas.product', [$business, $reel->products->first()]) }}"
                                    wire:navigate
                                    class="absolute right-3 top-3 z-30 inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-semibold text-zinc-900 shadow"
                                >
                                    <flux:icon.cube class="size-3.5" variant="outline" />
                                    {{ $reel->products->first()->name }}
                                </a>
                            @endif
                        </div>

                        <div class="p-4">
                            <div class="flex items-center justify-between gap-3">
                                <a href="{{ route('vitrinas.show', $business) }}" wire:navigate class="flex min-w-0 items-center gap-3">
                                    <div class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-full border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-950">
                                        @if ($business->logoUrl())
                                            <img src="{{ $business->logoUrl() }}" class="size-full object-cover" alt="{{ $business->name }}" loading="lazy">
                                        @else
                                            <flux:icon.building-storefront class="size-4 text-zinc-400" variant="outline" />
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-zinc-950 dark:text-white">{{ $business->name }}</p>
                                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $business->municipality?->name }} · {{ $reel->published_at?->diffForHumans() }}
                                        </p>
                                    </div>
                                </a>

                                <livewire:follow-button :business="$business" compact :key="'follow-reel-'.$business->id.'-'.$reel->id" />
                            </div>

                            @if ($reel->body)
                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-200">{{ $reel->body }}</p>
                            @endif

                            <div class="mt-3 flex items-center gap-1 border-t border-zinc-100 pt-2 dark:border-zinc-800">
                                <livewire:post-reaction-button :post="$reel" :key="'reaction-reel-'.$reel->id" />

                                <button
                                    type="button"
                                    x-data
                                    x-on:click="navigator.share ? navigator.share({ title: {{ Js::from($business->name) }}, url: {{ Js::from(route('vitrinas.show', $business)) }} }) : $flux.toast({ text: {{ Js::from(__('Copia el enlace desde tu navegador para compartir.')) }} })"
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-sm font-medium text-zinc-500 transition hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                                >
                                    <flux:icon.share class="size-4" variant="outline" />
                                    {{ __('Compartir') }}
                                </button>

                                <livewire:favorite-button :favoritable="$reel" compact :key="'save-reel-'.$reel->id" />
                            </div>

                            <livewire:post-comments :post="$reel" :key="'comments-reel-'.$reel->id" />
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $reels->links() }}
            </div>
        @endif
    </div>
</x-layouts::cliente>
