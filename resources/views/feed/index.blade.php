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

        .feed-left-sidebar {
            scrollbar-width: none;
        }

        .feed-left-sidebar::-webkit-scrollbar {
            display: none;
        }

        .feed-right-sidebar {
            display: none;
        }

        @media (min-width: 1024px) {
            .feed-layout {
                grid-template-columns: 280px minmax(0, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .feed-layout {
                grid-template-columns: 250px minmax(0, 670px) 280px;
                max-width: 1280px;
            }

            .feed-right-sidebar {
                display: block;
            }
        }
    </style>

    <div class="feed-layout mx-auto grid max-w-7xl gap-4 px-4 py-5 sm:px-6">
        @include('feed.partials.sidebar')

        <div class="min-w-0">
            @auth
                <x-push-notification-settings class="mb-4" />
            @endauth

            <section class="mb-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Historias') }}</flux:heading>
                    <flux:link :href="route('reels')" wire:navigate class="text-xs">{{ __('Ver todas') }} →</flux:link>
                </div>
                <livewire:stories-rail :municipality="$municipality" />
            </section>

            @if ($activeLive)
                @include('feed.partials.live-card', ['live' => $activeLive])
            @endif

            @if ($promotedProduct?->promotable)
                @include('feed.partials.promoted-product', ['promotion' => $promotedProduct, 'product' => $promotedProduct->promotable])
            @endif

            <section class="mb-4 flex items-center justify-between rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Publicaciones para ti') }}</flux:heading>
                <flux:dropdown position="bottom" align="end">
                    <flux:button size="sm" variant="ghost" icon-trailing="chevron-down">
                        {{ $tab === 'siguiendo' ? __('Siguiendo') : __('Más recientes') }}
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item :href="route('home')" wire:navigate>{{ __('Más recientes') }}</flux:menu.item>
                        <flux:menu.item :href="route('home', ['tab' => 'siguiendo'])" wire:navigate>{{ __('Siguiendo') }}</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </section>

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
