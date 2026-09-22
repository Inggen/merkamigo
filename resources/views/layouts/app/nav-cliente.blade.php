<flux:sidebar.group :heading="__('Cliente')" class="grid">
    <flux:sidebar.item icon="home" :href="route('home')" :current="request()->routeIs('home', 'feed')" wire:navigate>
        {{ __('Inicio') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="film" :href="route('reels')" :current="request()->routeIs('reels')" wire:navigate>
        {{ __('Reels') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="magnifying-glass" :href="route('explorar')" :current="request()->routeIs('explorar')" wire:navigate>
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

    <flux:sidebar.item icon="shopping-bag" :href="route('clientes.compras')" :current="request()->routeIs('clientes.compras')" wire:navigate>
        {{ __('Mis compras') }}
    </flux:sidebar.item>
</flux:sidebar.group>
