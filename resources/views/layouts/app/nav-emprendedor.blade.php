@php $primaryBusiness = auth()->user()->businesses()->first(); @endphp

<flux:sidebar.group :heading="__('Emprendedor')" class="grid">
    <flux:sidebar.item icon="home" :href="route('emprendedores.home')" :current="request()->routeIs('emprendedores.home')" wire:navigate>
        {{ __('Inicio') }}
    </flux:sidebar.item>

    @if ($primaryBusiness)
        <flux:sidebar.item icon="building-storefront" :href="route('emprendedores.negocios.vitrina', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.vitrina')" wire:navigate>
            {{ __('Mi vitrina') }}
        </flux:sidebar.item>

        <flux:sidebar.item icon="cube" :href="route('emprendedores.negocios.productos', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.productos')" wire:navigate>
            {{ __('Productos') }}
        </flux:sidebar.item>
    @else
        <flux:sidebar.item icon="building-storefront" :href="route('emprendedores.crear-vitrina')" :current="request()->routeIs('emprendedores.crear-vitrina')" wire:navigate>
            {{ __('Crear mi vitrina') }}
        </flux:sidebar.item>
    @endif

    @if ($primaryBusiness)
        <flux:sidebar.item icon="hand-raised" :href="route('emprendedores.negocios.oportunidades', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.oportunidades')" wire:navigate>
            {{ __('Oportunidades') }}
        </flux:sidebar.item>
    @endif

    @if ($primaryBusiness)
        <flux:sidebar.item icon="megaphone" :href="route('emprendedores.negocios.copiloto', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.copiloto')" wire:navigate>
            {{ __('Promocionar') }}
        </flux:sidebar.item>
    @endif

    <flux:sidebar.item icon="lifebuoy" :href="route('soporte')" :current="request()->routeIs('soporte')" wire:navigate>
        {{ __('Ayuda') }}
    </flux:sidebar.item>
</flux:sidebar.group>
