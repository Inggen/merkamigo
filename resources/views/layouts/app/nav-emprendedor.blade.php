<flux:sidebar.group :heading="__('Emprendedor')" class="grid">
    <flux:sidebar.item icon="home" :href="route('emprendedores.home')" :current="request()->routeIs('emprendedores.home')" wire:navigate>
        {{ __('Inicio') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="building-storefront" :href="route('emprendedores.crear-vitrina')" :current="request()->routeIs('emprendedores.crear-vitrina')" wire:navigate>
        {{ __('Mi vitrina') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="cube" :href="route('emprendedores.home')" badge="Pronto" wire:navigate>
        {{ __('Productos') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="hand-raised" :href="route('emprendedores.home')" badge="Pronto" wire:navigate>
        {{ __('Oportunidades') }}
    </flux:sidebar.item>

    <flux:sidebar.item icon="megaphone" :href="route('emprendedores.home')" badge="Pronto" wire:navigate>
        {{ __('Promocionar') }}
    </flux:sidebar.item>
</flux:sidebar.group>
