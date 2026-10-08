{{--
    Menú simplificado del Cliente (2026-10-03, a pedido del usuario): mismos
    accesos que `feed/partials/sidebar.blade.php`, consistentes en todas
    las vistas del Cliente. Los accesos secundarios permanecen disponibles
    desde sus secciones correspondientes.

    Pedido del usuario (2026-10-06): `/feed` y `/` eran la misma pantalla
    — ya no hace falta un "Comunidad" aparte aquí, "Inicio" ya es esa
    misma pantalla.
--}}
<flux:sidebar.group :heading="__('Cliente')" class="grid">
    <flux:sidebar.item icon="home" :href="route('home')" :current="request()->routeIs('home')" wire:navigate>
        {{ __('Inicio') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="building-storefront" :href="route('explorar')" :current="request()->routeIs('explorar')" wire:navigate>
        {{ __('Vitrinas') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="calendar-days" :href="route('eventos')" :current="request()->routeIs('eventos', 'eventos.show')" wire:navigate>
        {{ __('Eventos') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="sparkles" :href="route('merkapuntos')" :current="request()->routeIs('merkapuntos')" wire:navigate>
        {{ __('Merkapuntos') }}
    </flux:sidebar.item>
</flux:sidebar.group>
