@php
    $pageTitle = __('Merkamigo: negocios y productos locales cerca de ti');
    $pageDescription = $municipality
        ? __('Publicaciones, negocios y productos locales en :municipio con Merkamigo.', ['municipio' => $municipality->name])
        : __('Descubre publicaciones, negocios y productos locales en Bogotá y Sabana Norte con Merkamigo.');
@endphp

<x-layouts::cliente :title="$pageTitle" :description="$pageDescription" :canonical="route('home')">
    <h1 class="sr-only">{{ __('Inicio de Merkamigo') }}</h1>

    <style>
        .feed-sticky-sidebar {
            position: sticky;
            top: 5.5rem;
            align-self: start;
            max-height: calc(100vh - 6.5rem);
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: thin;
        }

        .feed-right-sidebar {
            display: none;
        }

        @media (min-width: 1280px) {
            .feed-layout {
                grid-template-columns: minmax(0, 1fr) 340px;
            }

            .feed-right-sidebar {
                display: block;
            }
        }
    </style>

    <div class="feed-layout mx-auto grid max-w-7xl gap-4 px-4 py-5 sm:px-6">
        <div class="min-w-0">
            @auth
                <x-push-notification-settings class="mb-4" />
            @endauth

            @if ($hasActiveStories)
                <section class="mb-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-3 flex items-center justify-between">
                        <flux:heading size="lg">{{ __('Historias') }}</flux:heading>
                        <flux:link :href="route('reels')" wire:navigate class="text-xs">{{ __('Ver todas') }} →</flux:link>
                    </div>
                    <livewire:stories-rail :municipality="$municipality" />
                </section>
            @endif

            @if ($activeLive)
                @include('feed.partials.live-card', ['live' => $activeLive])
            @endif

            @if ($promotedProduct?->promotable)
                @include('feed.partials.promoted-product', ['promotion' => $promotedProduct, 'product' => $promotedProduct->promotable])
            @endif

            @auth
                <livewire:feed-post-composer />
            @endauth

            <div class="mb-3 flex items-center justify-between gap-3 px-1">
                <flux:heading size="lg" class="text-zinc-700 dark:text-zinc-200">{{ __('Publicaciones para ti') }}</flux:heading>
                <flux:dropdown position="bottom" align="end">
                    <flux:button size="xs" variant="ghost" icon-trailing="chevron-down" class="text-zinc-500">
                        {{ $tab === 'siguiendo' ? __('Siguiendo') : __('Más recientes') }}
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item :href="route('home')" wire:navigate>{{ __('Más recientes') }}</flux:menu.item>
                        <flux:menu.item :href="route('home', ['tab' => 'siguiendo'])" wire:navigate>{{ __('Siguiendo') }}</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>

            @guest
                @if ($tab === 'siguiendo')
                    <x-states.empty
                        :title="__('Inicia sesión para ver tu feed de negocios seguidos')"
                        :description="__('Sigue negocios desde su vitrina para ver sus publicaciones aquí.')"
                    />
                @endif
            @endguest

            @if ($posts->isEmpty())
                <x-states.empty
                    :title="$tab === 'siguiendo' ? __('Todavía no sigues negocios con publicaciones') : __('Todavía no hay publicaciones')"
                    :description="$tab === 'siguiendo' ? __('Sigue negocios desde su vitrina para ver sus publicaciones aquí.') : __('Vuelve pronto — los negocios de tu municipio publicarán novedades aquí.')"
                />
            @else
                <div class="space-y-4">
                    @foreach ($posts as $post)
                        @include('feed.partials.post-card', ['post' => $post])
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $posts->links() }}
                </div>
            @endif
        </div>

        @include('feed.partials.right-sidebar')
    </div>
</x-layouts::cliente>
