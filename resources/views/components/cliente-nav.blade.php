@php
    $unreadNotifications = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    $firstName = auth()->check() ? \Illuminate\Support\Str::before(trim(auth()->user()->name), ' ') : null;
    $isPlazaView = request()->routeIs(
        'explorar',
        'clientes.home',
        'buscar',
        'municipios',
        'categorias',
        'categorias.show',
        'labs.generic-plaza',
    );
@endphp

<header
    class="sticky bg-white z-50 top-0 dark:bg-zinc-800"
    style="box-shadow: 0 0 15px rgba(0, 0, 0, .2);"
>
    <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:px-6">
        <a
            href="{{ route('home') }}"
            class="flex shrink-0 items-center gap-2.5"
            wire:navigate
            aria-label="{{ __('Ir al inicio de Merkamigo') }}"
            title="{{ __('Merkamigo') }}"
        >
            <x-app-logo-icon class="h-10 w-auto" />
            <x-brand-wordmark size="lg" class="hidden sm:inline" />
            <span class="sr-only">{{ __('Merkamigo') }}</span>
        </a>

        <nav aria-label="{{ __('Navegación principal') }}" class="ml-auto flex shrink-0 items-center gap-1">
            <flux:button
                size="sm"
                variant="ghost"
                :icon="$isPlazaView ? 'newspaper' : 'magnifying-glass'"
                :href="$isPlazaView ? route('feed') : route('explorar')"
                wire:navigate
            >
                <span class="hidden md:inline">{{ $isPlazaView ? __('Publicaciones') : __('Explorar') }}</span>
            </flux:button>

            @auth
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="bell"
                    :href="route('clientes.actividad')"
                    wire:navigate
                    class="relative"
                >
                    <span class="hidden md:inline">{{ __('Mensajes') }}</span>
                    @if ($unreadNotifications > 0)
                        <span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-semibold leading-5 text-white">
                            {{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}
                        </span>
                    @endif
                </flux:button>

                <div class="mx-2 hidden h-8 w-px bg-zinc-200 md:block dark:bg-zinc-700"></div>

                <flux:dropdown position="bottom" align="end">
                    <button type="button" class="flex items-center gap-2 rounded-xl p-1.5 text-sm text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-700">
                        <flux:avatar
                            :src="auth()->user()->avatarUrl()"
                            :initials="auth()->user()->initials()"
                            circle
                            size="sm"
                        />
                        <span class="hidden whitespace-nowrap md:inline">
                            {{ __('Hola,') }} <strong class="font-semibold text-zinc-900 dark:text-white">{{ $firstName }}</strong>
                        </span>
                        <flux:icon.chevron-down class="hidden size-4 md:block" variant="outline" />
                    </button>
                    <flux:menu>
                        <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>{{ __('Mi cuenta') }}</flux:menu.item>
                        <flux:menu.item :href="route('clientes.favoritos')" icon="heart" wire:navigate>{{ __('Favoritos') }}</flux:menu.item>
                        <flux:menu.separator />
                        <x-experience-switch-menu />
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                                {{ __('Cerrar sesión') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @else
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="user-circle"
                    :href="route('login')"
                    wire:navigate
                >
                    {{ __('Ingresa') }}
                </flux:button>
            @endauth
        </nav>
    </div>
</header>
