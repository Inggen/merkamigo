@php
    $unread = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    $guestLoginUrl = route('login');
    $guestFavoritesMessage = __('Necesitas ingresar o crear una cuenta para guardar favoritos.');
    $guestNeedsMessage = __('Necesitas ingresar o crear una cuenta para publicar en Pídelo.');
@endphp

<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white pb-[env(safe-area-inset-bottom)] md:hidden dark:border-zinc-700 dark:bg-zinc-800">
    <div class="mx-auto flex max-w-7xl items-stretch justify-between px-2">
        <a href="{{ route('buscar') }}" wire:navigate class="flex flex-1 flex-col items-center gap-0.5 py-2 text-xs {{ request()->routeIs('buscar') ? 'text-brand-600 dark:text-brand-400' : 'text-zinc-500 dark:text-zinc-400' }}">
            <flux:icon.magnifying-glass class="size-5" variant="outline" />
            {{ __('Explorar') }}
        </a>

        @auth
            <a href="{{ route('clientes.actividad') }}" wire:navigate class="relative flex flex-1 flex-col items-center gap-0.5 py-2 text-xs {{ request()->routeIs('clientes.actividad') ? 'text-brand-600 dark:text-brand-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                <flux:icon.bell class="size-5" variant="outline" />
                {{ __('Actividad') }}
                @if ($unread > 0)
                    <span class="absolute top-1 right-1/3 flex size-2 rounded-full bg-brand-500"></span>
                @endif
            </a>

            <a href="{{ route('pidelo.nueva') }}" wire:navigate class="flex flex-1 flex-col items-center gap-0.5 py-1.5">
                <span class="flex size-10 items-center justify-center rounded-full bg-brand-500 text-white shadow-sm">
                    <flux:icon.shopping-bag class="size-5" variant="outline" />
                </span>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Pídelo') }}</span>
            </a>

            <a href="{{ route('clientes.favoritos') }}" wire:navigate class="flex flex-1 flex-col items-center gap-0.5 py-2 text-xs {{ request()->routeIs('clientes.favoritos') ? 'text-brand-600 dark:text-brand-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                <flux:icon.heart class="size-5" variant="outline" />
                {{ __('Favoritos') }}
            </a>

            <a href="{{ route('dashboard') }}" wire:navigate class="flex flex-1 flex-col items-center gap-0.5 py-2 text-xs {{ request()->routeIs('dashboard') ? 'text-brand-600 dark:text-brand-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                <flux:icon.user class="size-5" variant="outline" />
                {{ __('Perfil') }}
            </a>
        @else
            <a
                href="{{ route('pidelo.nueva') }}"
                class="flex flex-1 flex-col items-center gap-0.5 py-1.5 text-xs text-zinc-500 dark:text-zinc-400"
                x-data
                x-on:click.prevent="$flux.toast({ text: '{{ e($guestNeedsMessage) }}' }); setTimeout(() => window.location.href = '{{ $guestLoginUrl }}', 900)"
            >
                <span class="flex size-10 items-center justify-center rounded-full bg-brand-500 text-white shadow-sm">
                    <flux:icon.shopping-bag class="size-5" variant="outline" />
                </span>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Pídelo') }}</span>
            </a>

            <a
                href="{{ route('clientes.favoritos') }}"
                class="flex flex-1 flex-col items-center gap-0.5 py-2 text-xs text-zinc-500 dark:text-zinc-400"
                x-data
                x-on:click.prevent="$flux.toast({ text: '{{ e($guestFavoritesMessage) }}' }); setTimeout(() => window.location.href = '{{ $guestLoginUrl }}', 900)"
            >
                <flux:icon.heart class="size-5" variant="outline" />
                {{ __('Favoritos') }}
            </a>

            <a href="{{ route('login') }}" wire:navigate class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs text-zinc-700 dark:text-zinc-200">
                <span class="rounded-xl border border-brand-300 px-3 py-2 font-medium text-brand-700">{{ __('Ingresa') }}</span>
            </a>

            <a href="{{ route('emprendedores.bienvenida') }}" wire:navigate class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs">
                <span class="rounded-xl bg-brand-600 px-3 py-2 font-medium text-white shadow-sm">{{ __('Publica tu negocio') }}</span>
            </a>
        @endauth
    </div>
</nav>
