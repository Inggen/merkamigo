@php $primaryBusiness = auth()->user()->businesses()->first(); @endphp

<flux:sidebar.group :heading="__('Emprendedor')" class="grid">
    <flux:sidebar.item icon="home" :href="route('emprendedores.home')" :current="request()->routeIs('emprendedores.home')" wire:navigate>
        {{ __('Inicio') }}
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

        <flux:sidebar.item icon="sparkles" :href="route('emprendedores.negocios.impulsar', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.impulsar')" wire:navigate>
            {{ __('Impulsa tu negocio') }}
        </flux:sidebar.item>

        <flux:sidebar.item icon="shield-check" :href="route('emprendedores.negocios.verificacion', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.verificacion')" wire:navigate>
            {{ __('Pasaporte de confianza') }}
        </flux:sidebar.item>

        <flux:sidebar.item icon="credit-card" :href="route('emprendedores.negocios.plan', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.plan')" wire:navigate>
            {{ __('Tu plan') }}
        </flux:sidebar.item>

        @if ($primaryBusiness->canUseAiChatbot() || (auth()->user()?->canBypassPlanGates() ?? false))
            <flux:sidebar.item icon="chat-bubble-left-right" :href="route('emprendedores.negocios.chatbot', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.chatbot')" wire:navigate>
                {{ __('Chatbot IA') }}
            </flux:sidebar.item>
        @endif
    @endif

    <flux:sidebar.item icon="lifebuoy" :href="route('soporte')" :current="request()->routeIs('soporte')" wire:navigate>
        {{ __('Ayuda') }}
    </flux:sidebar.item>
</flux:sidebar.group>
