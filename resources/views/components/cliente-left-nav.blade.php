@php
    // Nav compartida del sidebar izquierdo del Cliente (2026-10-03): extraída
    // de `feed/partials/sidebar.blade.php` para reutilizarla también en
    // `layouts/cliente.blade.php` (páginas internas como Mensajes, Pídelo
    // nueva y Mis solicitudes) sin duplicar el menú en dos sitios.
    //
    // Pedido del usuario (2026-10-06): `/feed` y `/` eran la misma pantalla
    // — ya no hace falta un "Comunidad" aparte aquí, "Inicio" (abajo) es
    // esa misma pantalla.
    $unread = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    $menuItems = [
        ['label' => __('Vitrinas'), 'icon' => 'building-storefront', 'url' => route('explorar'), 'routes' => ['explorar']],
        ['label' => __('Eventos'), 'icon' => 'calendar-days', 'url' => route('eventos'), 'routes' => ['eventos', 'eventos.show']],
        ['label' => __('Merkapuntos'), 'icon' => 'sparkles', 'url' => route('merkapuntos'), 'routes' => ['merkapuntos']],
    ];
@endphp

<nav aria-label="{{ __('Explorar Merkamigo') }}" class="space-y-1">
    @php $homeActive = request()->routeIs('home'); @endphp
    <a href="{{ route('home') }}" wire:navigate @class([
        'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
        'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $homeActive,
        'text-zinc-700 hover:bg-zinc-50 hover:text-brand-700 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-brand-300' => ! $homeActive,
    ])>
        <flux:icon.home class="size-5" :variant="$homeActive ? 'solid' : 'outline'" />
        {{ __('Inicio') }}
    </a>

    {{ $slot ?? '' }}

    @foreach ($menuItems as $item)
        @php $isActive = request()->routeIs(...$item['routes']); @endphp
        <a href="{{ $item['url'] }}" wire:navigate @class([
            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
            'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $isActive,
            'text-zinc-700 hover:bg-zinc-50 hover:text-brand-700 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-brand-300' => ! $isActive,
        ])>
            <flux:icon :name="$item['icon']" class="size-5" :variant="$isActive ? 'solid' : 'outline'" />
            {{ $item['label'] }}
        </a>
    @endforeach

    <a href="{{ auth()->check() ? route('clientes.actividad') : route('login') }}" wire:navigate class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 hover:text-brand-700 dark:text-zinc-200 dark:hover:bg-zinc-800 dark:hover:text-brand-300">
        <flux:icon.bell class="size-5" variant="outline" />
        <span class="flex-1">{{ __('Notificaciones') }}</span>
        @if ($unread > 0)
            <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-brand-600 px-1.5 text-xs font-semibold leading-6 text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </a>
</nav>
