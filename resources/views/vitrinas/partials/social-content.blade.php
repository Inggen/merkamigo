@php
    $storyItems = $highlightedStories->map(fn ($story) => [
        'image' => $story->imageUrl(),
        'caption' => $story->caption,
        'productUrl' => $story->product ? route('vitrinas.product', [$business, $story->product]) : null,
    ])->values();
@endphp

<div class="space-y-8">
    @if ($highlightedStories->isNotEmpty())
        <section x-data="{ stories: @js($storyItems), active: null }">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Historias destacadas') }}</h3>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $highlightedStories->count() }}</span>
            </div>

            <div class="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($highlightedStories as $story)
                    <button type="button" x-on:click="active = {{ $loop->index }}" class="group w-32 shrink-0 snap-start text-left sm:w-40">
                        <span class="block aspect-[4/5] overflow-hidden rounded-2xl bg-zinc-100 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                            <img src="{{ $story->imageUrl() }}" class="size-full object-cover transition duration-300 group-hover:scale-105" alt="{{ $story->caption ?: __('Historia destacada de :business', ['business' => $business->name]) }}" loading="lazy">
                        </span>
                        <span class="mt-2 line-clamp-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $story->caption ?: $business->name }}</span>
                    </button>
                @endforeach
            </div>

            <template x-teleport="body">
                <div x-show="active !== null" x-cloak x-on:keydown.escape.window="active = null" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4" role="dialog" aria-modal="true">
                    <button type="button" x-on:click="active = null" class="absolute right-4 top-4 flex size-11 items-center justify-center rounded-full bg-white text-zinc-900" aria-label="{{ __('Cerrar') }}">
                        <flux:icon.x-mark class="size-6" />
                    </button>
                    <button type="button" x-show="stories.length > 1" x-on:click="active = (active - 1 + stories.length) % stories.length" class="absolute left-4 flex size-11 items-center justify-center rounded-full bg-white text-zinc-900" aria-label="{{ __('Anterior') }}">
                        <flux:icon.chevron-left class="size-6" />
                    </button>
                    <div class="flex max-h-[88vh] w-full max-w-md flex-col overflow-hidden rounded-3xl bg-zinc-950 shadow-2xl">
                        <img x-bind:src="stories[active]?.image" x-bind:alt="stories[active]?.caption || ''" class="min-h-0 flex-1 object-contain">
                        <div class="p-4 text-white">
                            <p x-show="stories[active]?.caption" x-text="stories[active]?.caption" class="text-sm"></p>
                            <a x-show="stories[active]?.productUrl" x-bind:href="stories[active]?.productUrl" class="mt-3 inline-flex rounded-full bg-white px-4 py-2 text-sm font-semibold text-zinc-900">{{ __('Ver producto') }}</a>
                        </div>
                    </div>
                    <button type="button" x-show="stories.length > 1" x-on:click="active = (active + 1) % stories.length" class="absolute right-4 flex size-11 items-center justify-center rounded-full bg-white text-zinc-900" aria-label="{{ __('Siguiente') }}">
                        <flux:icon.chevron-right class="size-6" />
                    </button>
                </div>
            </template>
        </section>
    @endif

    @if ($storefrontPosts->isNotEmpty())
        <section>
            <h3 class="mb-3 text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Publicaciones') }}</h3>
            <div class="grid gap-4 xl:grid-cols-2">
                @foreach ($storefrontPosts as $post)
                    @include('feed.partials.post-card', ['post' => $post])
                @endforeach
            </div>
        </section>
    @endif

    @if ($storefrontReels->isNotEmpty())
        <section>
            <h3 class="mb-3 text-lg font-semibold text-zinc-950 dark:text-white">{{ __('Reels') }}</h3>
            <div class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($storefrontReels as $reel)
                    @php($video = $reel->media->first())
                    <article class="relative w-[min(82vw,24rem)] shrink-0 snap-start overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                        <x-owner-edit-link
                            :business="$business"
                            :href="route('emprendedores.negocios.reels', $business).'#reel-'.$reel->id"
                            :label="__('Gestionar reel')"
                            compact
                            class="absolute right-3 top-3 z-20 bg-white/90 shadow-sm dark:bg-zinc-900/90"
                        />
                        @if ($video)
                            <x-media.video-player :src="$video->url()" aspect="feed" fit="contain" loop />
                        @endif
                        @if ($reel->body)
                            <p class="line-clamp-3 p-4 text-sm leading-6 text-zinc-700 dark:text-zinc-200">{{ $reel->body }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
