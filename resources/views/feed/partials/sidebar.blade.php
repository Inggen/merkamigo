@php
    $isClient = request()->routeIs('home', 'feed') || (auth()->check() && auth()->user()->experience === 'cliente');
    $unread = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    $localImage = $municipality?->coverUrl() ?? asset('images/backgrounds/fondo-buscador-principal.webp');
    $menuItems = [
        ['label' => __('Restaurantes'), 'icon' => 'building-storefront', 'url' => route('buscar', ['q' => __('Restaurantes')])],
        ['label' => __('Tiendas'), 'icon' => 'shopping-bag', 'url' => route('buscar', ['q' => __('Tiendas')])],
        ['label' => __('Servicios'), 'icon' => 'wrench-screwdriver', 'url' => route('buscar', ['q' => __('Servicios')])],
        ['label' => __('Eventos'), 'icon' => 'calendar-days', 'url' => route('buscar', ['q' => __('Eventos')])],
        ['label' => __('Salud'), 'icon' => 'heart', 'url' => route('buscar', ['q' => __('Salud')])],
        ['label' => __('Digitales'), 'icon' => 'computer-desktop', 'url' => route('buscar', ['q' => __('Digitales')])],
        ['label' => __('Suscripciones'), 'icon' => 'sparkles', 'url' => route('buscar', ['q' => __('Suscripciones')])],
    ];
@endphp

<aside class="feed-left-sidebar feed-sticky-sidebar hidden space-y-4 lg:block">
    @auth
        <div class="rounded-2xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-3 flex items-center justify-between px-1">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Mi rol en Merkamigo') }}</p>
                <flux:icon.arrows-right-left class="size-4 text-zinc-500" variant="outline" />
            </div>

            <div class="grid grid-cols-2 gap-2">
                <form method="POST" action="{{ route('experience.update') }}">
                    @csrf
                    <input type="hidden" name="experience" value="cliente">
                    <button type="submit" @class([
                        'flex w-full items-center justify-center gap-1.5 rounded-xl px-2 py-2 text-xs font-semibold transition',
                        'bg-brand-600 text-white' => $isClient,
                        'border border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800' => ! $isClient,
                    ])>
                        <flux:icon.user class="size-4" variant="solid" />
                        {{ __('Comprador') }}
                    </button>
                </form>

                <form method="POST" action="{{ route('experience.update') }}">
                    @csrf
                    <input type="hidden" name="experience" value="emprendedor">
                    <button type="submit" @class([
                        'flex w-full items-center justify-center gap-1.5 rounded-xl px-2 py-2 text-xs font-semibold transition',
                        'bg-brand-600 text-white' => ! $isClient,
                        'border border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800' => $isClient,
                    ])>
                        <flux:icon.building-storefront class="size-4" variant="outline" />
                        {{ __('Mi negocio') }}
                    </button>
                </form>
            </div>
        </div>
    @endauth

    <div class="rounded-2xl border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <nav aria-label="{{ __('Explorar Merkamigo') }}" class="space-y-1">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3 rounded-xl bg-brand-50 px-3 py-2.5 text-sm font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                <flux:icon.home class="size-5" variant="solid" />
                {{ __('Inicio') }}
            </a>

            <form method="GET" action="{{ route('buscar') }}">
                <x-clientes.near-me-toggle menu />
            </form>

            @foreach ($menuItems as $item)
                <a href="{{ $item['url'] }}" wire:navigate class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 hover:text-brand-700 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-brand-300">
                    <flux:icon :name="$item['icon']" class="size-5" variant="outline" />
                    {{ $item['label'] }}
                </a>
            @endforeach

            <div class="my-2 border-t border-zinc-100 dark:border-zinc-800"></div>

            <a href="{{ auth()->check() ? route('clientes.actividad') : route('login') }}" wire:navigate class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 hover:text-brand-700 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-brand-300">
                <flux:icon.bell class="size-5" variant="outline" />
                <span class="flex-1">{{ __('Notificaciones') }}</span>
                @if ($unread > 0)
                    <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-brand-600 px-1.5 text-xs font-semibold leading-6 text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
                @endif
            </a>
        </nav>
    </div>

    <div class="rounded-2xl border border-brand-100 bg-brand-50 p-4 text-center dark:border-transparent dark:bg-[#6e2006]">
        <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-white text-brand-600 shadow-sm dark:bg-zinc-900">
            <flux:icon.user-group class="size-7" variant="outline" />
        </div>
        <p class="font-semibold leading-tight text-zinc-900 dark:text-white">{{ __('Una comunidad más fuerte, local') }}</p>
        <p class="mt-2 text-xs leading-5 text-zinc-600 dark:text-zinc-300">{{ __('Apoya negocios, conecta con personas y haz crecer tu barrio.') }}</p>
        <a href="{{ route('como-funciona') }}" wire:navigate class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
            {{ __('Conoce más') }}
            <flux:icon.arrow-right class="size-4" variant="outline" />
        </a>
    </div>

    <a href="{{ route('explorar') }}" wire:navigate class="block overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="p-3 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Descubre lo local') }}</div>
        <img src="{{ $localImage }}" alt="" class="h-28 w-full object-cover" loading="lazy">
    </a>
</aside>
