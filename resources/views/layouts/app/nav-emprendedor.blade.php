@php
    // Terreno de 1.1 del TODO social: "si un usuario administra varios
    // negocios, permitir seleccionar negocio activo". El negocio activo se
    // deriva del `{business}` de la URL cuando la página actual ya está
    // dentro de un negocio (route model binding, no un modo global nuevo
    // que duplicaría lo que la propia navegación por URL ya resuelve) —
    // solo cae a `first()` en páginas sin negocio en la URL (ej. Inicio).
    $businesses = auth()->user()->businesses;
    $routeBusiness = request()->route('business');
    $primaryBusiness = $routeBusiness instanceof \App\Domain\Businesses\Models\Business ? $routeBusiness : $businesses->first();
@endphp

<flux:sidebar.group :heading="__('Emprendedor')" class="grid">
    <flux:sidebar.item icon="home" :href="route('emprendedores.home')" :current="request()->routeIs('emprendedores.home')" wire:navigate>
        {{ __('Inicio') }}
    </flux:sidebar.item>

    @if ($businesses->count() > 1)
        <flux:dropdown class="w-full" position="bottom" align="start">
            <flux:button icon-trailing="chevron-down" size="sm" variant="ghost" class="w-full !justify-between !px-2 !font-normal">
                <span class="truncate">{{ $primaryBusiness?->name ?? __('Selecciona un negocio') }}</span>
            </flux:button>

            <flux:menu>
                @foreach ($businesses as $business)
                    <flux:menu.item :href="route('emprendedores.negocios.vitrina', $business)" wire:navigate>
                        <span class="me-1 inline-flex w-4 shrink-0 items-center justify-center text-zinc-400 dark:text-white/60">
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
