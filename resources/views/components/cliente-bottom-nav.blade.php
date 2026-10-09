@php
    $items = [
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
            'label' => __('Pídelo'),
            'icon' => 'shopping-bag',
            'url' => route('pidelo.nueva'),
            'active' => request()->routeIs('pidelo.nueva', 'mis-solicitudes'),
            'featured' => true,
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

<nav
    data-client-bottom-nav
    aria-label="{{ __('Navegación principal móvil') }}"
    class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 pb-[env(safe-area-inset-bottom)] shadow-[0_-8px_24px_-18px_rgba(0,0,0,0.45)] backdrop-blur md:hidden dark:border-zinc-700 dark:bg-zinc-900/95"
>
    <div class="mx-auto grid max-w-lg grid-cols-5 px-2">
        @foreach ($items as $item)
            @if ($item['featured'] ?? false)
                <a
                    href="{{ $item['url'] }}"
                    wire:navigate
                    @if ($item['active']) aria-current="page" @endif
                    class="relative -mt-4 flex min-w-0 flex-col items-center gap-1 px-1 pb-2 text-[0.68rem] font-semibold text-zinc-600 dark:text-zinc-300"
                >
                    <span @class([
                        'flex size-12 items-center justify-center rounded-full border-4 border-white text-white shadow-lg transition dark:border-zinc-900',
                        'bg-brand-700' => $item['active'],
                        'bg-brand-600 hover:bg-brand-700' => ! $item['active'],
                    ])>
                        <flux:icon :name="$item['icon']" class="size-6" :variant="$item['active'] ? 'solid' : 'outline'" />
                    </span>
                    <span class="max-w-full truncate">{{ $item['label'] }}</span>
                </a>
            @else
                <a
                    href="{{ $item['url'] }}"
                    wire:navigate
                    @if ($item['active']) aria-current="page" @endif
                    @class([
                        'relative flex min-w-0 flex-col items-center justify-center gap-1 px-1 py-2.5 text-[0.68rem] font-medium transition',
                        'text-brand-700 dark:text-brand-300' => $item['active'],
                        'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' => ! $item['active'],
                    ])
                >
                    @if ($item['active'])
                        <span class="absolute inset-x-3 top-0 h-0.5 rounded-full bg-brand-600"></span>
                    @endif
                    <flux:icon :name="$item['icon']" class="size-5" :variant="$item['active'] ? 'solid' : 'outline'" />
                    <span class="max-w-full truncate">{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </div>
</nav>
