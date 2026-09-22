@php
    $businesses = auth()->user()->businesses;
    $routeBusiness = request()->route('business');
    $primaryBusiness = $routeBusiness instanceof \App\Domain\Businesses\Models\Business ? $routeBusiness : $businesses->first();
    $unread = auth()->user()->unreadNotifications()->count();
@endphp

<div class="px-3 pb-2 pt-1">
    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ __('Emprendedor') }}</p>
</div>

@if ($businesses->count() > 1)
    <flux:dropdown class="mb-2 w-full px-1" position="bottom" align="start">
        <flux:button icon-trailing="chevron-down" size="sm" variant="ghost" class="w-full !justify-between !px-2 !font-normal">
            <span class="truncate">{{ $primaryBusiness?->name ?? __('Selecciona un negocio') }}</span>
        </flux:button>

        <flux:menu>
            @foreach ($businesses as $business)
                <flux:menu.item :href="route('emprendedores.negocios.vitrina', $business)" wire:navigate>
                    <span class="me-1 inline-flex w-4 shrink-0 items-center justify-center text-zinc-400">
                        @if ($primaryBusiness?->id === $business->id)
                            <flux:icon.check variant="mini" class="size-4" />
                        @endif
                    </span>
                    <span class="truncate">{{ $business->name }}</span>
                </flux:menu.item>
            @endforeach
            <flux:menu.separator />
            <flux:menu.item :href="route('emprendedores.crear-vitrina')" icon="plus" wire:navigate>
                {{ __('Crear negocio') }}
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
@endif

<flux:sidebar.group :heading="__('General')" class="entrepreneur-nav-group grid">
    <flux:sidebar.item icon="home" :href="route('emprendedores.home')" :current="request()->routeIs('emprendedores.home')" wire:navigate>{{ __('Inicio') }}</flux:sidebar.item>
    <flux:sidebar.item icon="bell" :href="route('clientes.actividad')" :current="request()->routeIs('clientes.actividad')" :badge="$unread > 0 ? $unread : null" wire:navigate>{{ __('Actividad') }}</flux:sidebar.item>
    @if ($primaryBusiness)
        <flux:sidebar.item icon="hand-raised" :href="route('emprendedores.negocios.oportunidades', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.oportunidades')" wire:navigate>{{ __('Oportunidades') }}</flux:sidebar.item>
    @endif
</flux:sidebar.group>

<div class="mx-3 my-2 border-t border-zinc-200/80 dark:border-zinc-700"></div>

<flux:sidebar.group :heading="__('Mi negocio')" class="entrepreneur-nav-group grid">
    @if ($primaryBusiness)
        <flux:sidebar.item icon="building-storefront" :href="route('emprendedores.negocios.vitrina', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.vitrina')" wire:navigate>{{ __('Mi vitrina') }}</flux:sidebar.item>
        <flux:sidebar.item icon="cube" :href="route('emprendedores.negocios.productos', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.productos')" wire:navigate>{{ __('Productos') }}</flux:sidebar.item>

        <flux:sidebar.group expandable expanded :heading="__('Contenido')" icon="newspaper" class="entrepreneur-content-group">
            <flux:sidebar.item icon="rectangle-stack" :href="route('emprendedores.negocios.publicaciones', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.publicaciones')" wire:navigate>{{ __('Publicaciones') }}</flux:sidebar.item>
            <flux:sidebar.item icon="bolt" :href="route('emprendedores.negocios.estados', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.estados')" wire:navigate>{{ __('Estados') }}</flux:sidebar.item>
            <flux:sidebar.item icon="film" :href="route('emprendedores.negocios.reels', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.reels')" wire:navigate>{{ __('Reels') }}</flux:sidebar.item>
            <flux:sidebar.item icon="video-camera" :href="route('emprendedores.negocios.lives', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.lives')" wire:navigate>{{ __('En vivo') }}</flux:sidebar.item>
        </flux:sidebar.group>
    @else
        <flux:sidebar.item icon="building-storefront" :href="route('emprendedores.crear-vitrina')" :current="request()->routeIs('emprendedores.crear-vitrina')" wire:navigate>{{ __('Crear mi vitrina') }}</flux:sidebar.item>
    @endif
</flux:sidebar.group>

@if ($primaryBusiness)
    <div class="mx-3 my-2 border-t border-zinc-200/80 dark:border-zinc-700"></div>
    <flux:sidebar.group :heading="__('Ventas')" class="entrepreneur-nav-group grid">
        <flux:sidebar.item icon="shopping-bag" :href="route('emprendedores.negocios.ventas', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.ventas')" wire:navigate>{{ __('Ventas') }}</flux:sidebar.item>
        <flux:sidebar.item icon="credit-card" :href="route('emprendedores.negocios.cobros-en-linea', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.cobros-en-linea')" wire:navigate>{{ __('Cobros en línea') }}</flux:sidebar.item>
    </flux:sidebar.group>

    <div class="mx-3 my-2 border-t border-zinc-200/80 dark:border-zinc-700"></div>
    <flux:sidebar.group :heading="__('Impulso')" class="entrepreneur-nav-group grid">
        <flux:sidebar.item icon="megaphone" :href="route('emprendedores.negocios.copiloto', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.copiloto')" wire:navigate>{{ __('Promocionar') }}</flux:sidebar.item>
        <flux:sidebar.item icon="rocket-launch" :href="route('emprendedores.negocios.impulsar', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.impulsar')" wire:navigate>{{ __('Impulsa tu negocio') }}</flux:sidebar.item>
        <flux:sidebar.item icon="shield-check" :href="route('emprendedores.negocios.verificacion', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.verificacion')" wire:navigate>{{ __('Pasaporte de confianza') }}</flux:sidebar.item>
    </flux:sidebar.group>

    <div class="mx-3 my-2 border-t border-zinc-200/80 dark:border-zinc-700"></div>
    <flux:sidebar.group :heading="__('Configuración')" class="entrepreneur-nav-group grid">
        <flux:sidebar.item icon="credit-card" :href="route('emprendedores.negocios.plan', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.plan')" wire:navigate>{{ __('Tu plan') }}</flux:sidebar.item>
        @if ($primaryBusiness->canUseAiChatbot() || (auth()->user()?->canBypassPlanGates() ?? false))
            <flux:sidebar.item icon="chat-bubble-left-right" :href="route('emprendedores.negocios.chatbot', $primaryBusiness)" :current="request()->routeIs('emprendedores.negocios.chatbot')" wire:navigate>{{ __('Chatbot IA') }}</flux:sidebar.item>
        @endif
        <flux:sidebar.item icon="lifebuoy" :href="route('soporte')" :current="request()->routeIs('soporte')" wire:navigate>{{ __('Ayuda') }}</flux:sidebar.item>
    </flux:sidebar.group>
@else
    <flux:sidebar.item icon="lifebuoy" :href="route('soporte')" :current="request()->routeIs('soporte')" wire:navigate>{{ __('Ayuda') }}</flux:sidebar.item>
@endif
