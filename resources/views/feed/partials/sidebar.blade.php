@php
    $isClient = request()->routeIs('home') || (auth()->check() && auth()->user()->experience === 'cliente');
    $localImage = $municipality?->coverUrl() ?? asset('images/backgrounds/fondo-buscador-principal.webp');
@endphp

<aside class="feed-left-sidebar feed-sticky-sidebar hidden space-y-4 lg:block">
    

    <div class="rounded-2xl border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <x-cliente-left-nav>
            <form method="GET" action="{{ route('buscar') }}">
                <x-clientes.near-me-toggle menu />
            </form>
        </x-cliente-left-nav>
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
