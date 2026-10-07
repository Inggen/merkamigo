@php
    $firstName = auth()->check() ? \Illuminate\Support\Str::before(trim(auth()->user()->name), ' ') : null;
    $primaryNavigation = [
        [
            'label' => __('Vitrinas'),
            'icon' => 'building-storefront',
            'url' => route('explorar'),
            'active' => request()->routeIs('explorar', 'buscar', 'municipios', 'municipios.*', 'categorias', 'categorias.*', 'vitrinas.*'),
        ],
        [
            'label' => __('Comunidad'),
            'icon' => 'user-group',
            'url' => route('home'),
            'active' => request()->routeIs('home', 'reels', 'reels.*'),
        ],
        [
            'label' => __('Eventos'),
            'icon' => 'calendar-days',
            'url' => route('eventos'),
            'active' => request()->routeIs('eventos', 'eventos.*'),
        ],
        [
            'label' => __('Merkapuntos'),
            'icon' => 'sparkles',
            'url' => auth()->check() ? route('merkapuntos') : route('premia.index'),
            'active' => request()->routeIs('merkapuntos', 'premia.*'),
        ],
    ];
@endphp

<header
    class="sticky bg-white z-50 top-0 dark:bg-zinc-800"
    style="box-shadow: 0 0 15px rgba(0, 0, 0, .2);"
>
    <div class="mx-auto grid w-full max-w-[1600px] grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
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

        <nav aria-label="{{ __('Navegación principal') }}" class="hidden min-w-0 items-center justify-center gap-1 lg:flex xl:gap-2">
            @foreach ($primaryNavigation as $item)
                <a
                    href="{{ $item['url'] }}"
                    wire:navigate
                    @if ($item['active']) aria-current="page" @endif
                    @class([
                        'inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition xl:px-4',
                        'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $item['active'],
                        'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white' => ! $item['active'],
                    ])
                >
                    <flux:icon :name="$item['icon']" class="size-5" :variant="$item['active'] ? 'solid' : 'outline'" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="flex shrink-0 items-center justify-end gap-1">
            @auth
                {{--
                    Pedido del usuario (2026-10-06): un único desplegable
                    de notificaciones reemplaza los botones separados de
                    campana y mensajes — los mensajes nuevos llegan aquí
                    como una notificación más (tipo `business_message`) que
                    al hacer clic lleva directo a esa conversación.
                --}}
                <livewire:notification-bell />

                <div class="mx-1 hidden h-8 w-px bg-zinc-200 sm:block dark:bg-zinc-700"></div>

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
                        <flux:menu.item :href="route('clientes.actividad')" icon="bell" wire:navigate>{{ __('Actividad') }}</flux:menu.item>
                        <flux:menu.item :href="route('clientes.favoritos')" icon="heart" wire:navigate>{{ __('Favoritos') }}</flux:menu.item>
                        <flux:menu.separator />
                        <x-appearance-switcher />
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
        </div>
    </div>
</header>
