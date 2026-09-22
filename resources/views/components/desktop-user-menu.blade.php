@props(['placement' => 'sidebar'])

<flux:dropdown position="bottom" :align="$placement === 'header' ? 'end' : 'start'" {{ $attributes }}>
    @if ($placement === 'header')
        <flux:button
            variant="ghost"
            class="!h-10 !rounded-full !px-1.5 hover:!bg-zinc-100 dark:hover:!bg-zinc-800"
            data-test="header-user-menu-button"
            aria-label="{{ __('Abrir opciones de usuario') }}"
        >
            <flux:avatar
                :src="auth()->user()->avatarUrl()"
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                circle
                class="size-8"
            />
            <span class="hidden max-w-40 truncate pe-1 text-sm font-medium text-zinc-700 xl:block dark:text-zinc-200">
                {{ auth()->user()->name }}
            </span>
            <flux:icon.chevron-down class="me-1 size-4 text-zinc-400" />
        </flux:button>
    @else
        <flux:sidebar.profile
            :avatar="auth()->user()->avatarUrl()"
            :name="auth()->user()->name"
            :initials="auth()->user()->initials()"
            circle
            icon:trailing="chevrons-up-down"
            data-test="sidebar-menu-button"
        />
    @endif

    <flux:menu class="min-w-64">
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar
                :src="auth()->user()->avatarUrl()"
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                circle
            />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>
        <flux:menu.separator />
        <x-experience-switch-menu />
        <flux:menu.separator />
        <flux:menu.radio.group>
            <flux:menu.item :href="route('clientes.favoritos')" icon="heart" wire:navigate>
                {{ __('Favoritos') }}
            </flux:menu.item>
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                {{ __('Configuración') }}
            </flux:menu.item>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer"
                    data-test="logout-button"
                >
                    {{ __('Cerrar sesión') }}
                </flux:menu.item>
            </form>
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
