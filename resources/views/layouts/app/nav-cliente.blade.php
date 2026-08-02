<flux:sidebar.group :heading="__('Cliente')" class="grid">
    <flux:sidebar.item icon="magnifying-glass" :href="route('buscar')" :current="request()->routeIs('buscar')" wire:navigate>
        {{ __('Explorar') }}
    </flux:sidebar.item>

    <flux:sidebar.item
        icon="bell"
        :href="route('clientes.actividad')"
        :current="request()->routeIs('clientes.actividad')"
        :badge="($unread = auth()->user()->unreadNotifications()->count()) > 0 ? $unread : null"
        wire:navigate
    >
        {{ __('Actividad') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="heart" :href="route('clientes.favoritos')" :current="request()->routeIs('clientes.favoritos')" wire:navigate>
        {{ __('Favoritos') }}
    </flux:sidebar.item>
</flux:sidebar.group>
