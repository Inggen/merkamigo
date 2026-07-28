<flux:sidebar.group :heading="__('Cliente')" class="grid">
    <flux:sidebar.item icon="home" :href="route('clientes.home')" :current="request()->routeIs('clientes.home')" wire:navigate>
        {{ __('Inicio') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="magnifying-glass" :href="route('buscar')" :current="request()->routeIs('buscar')" wire:navigate>
        {{ __('Explorar') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="chat-bubble-left-right" :href="route('clientes.home')" badge="Pronto" wire:navigate>
        {{ __('Mensajes') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="heart" :href="route('clientes.favoritos')" :current="request()->routeIs('clientes.favoritos')" wire:navigate>
        {{ __('Favoritos') }}
    </flux:sidebar.item>
</flux:sidebar.group>
