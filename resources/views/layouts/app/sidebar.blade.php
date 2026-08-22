<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <div class="min-h-screen lg:flex">
            <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:sidebar.header>
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                    <flux:sidebar.collapse class="lg:hidden" />
                </flux:sidebar.header>

                <flux:sidebar.nav>
                    @if (auth()->user()->experience === 'cliente')
                        @include('layouts.app.nav-cliente')
                    @elseif (auth()->user()->experience === 'emprendedor')
                        @include('layouts.app.nav-emprendedor')
                    @else
                        <flux:sidebar.group :heading="__('Merkamigo')" class="grid">
                            <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                                {{ __('Elegir experiencia') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>
                    @endif
                </flux:sidebar.nav>

                <flux:spacer />

                <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
            </flux:sidebar>

            <div class="min-w-0 flex-1">
                <!-- Mobile User Menu -->
                <flux:header class="lg:hidden">
                    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

                    <flux:spacer />

                    <flux:dropdown position="top" align="end">
                        <flux:profile
                            :avatar="auth()->user()->avatarUrl()"
                            :initials="auth()->user()->initials()"
                            circle
                            icon-trailing="chevron-down"
                        />

                        <flux:menu>
                            <flux:menu.radio.group>
                                <div class="p-0 text-sm font-normal">
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
                                </div>
                            </flux:menu.radio.group>

                            <flux:menu.separator />

                            <x-experience-switch-menu />

                            <flux:menu.separator />

                            <flux:menu.radio.group>
                                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                    {{ __('Configuración') }}
                                </flux:menu.item>
                            </flux:menu.radio.group>

                            <flux:menu.separator />

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
                        </flux:menu>
                    </flux:dropdown>
                </flux:header>

                <div class="{{ auth()->user()->experience === 'cliente' ? 'pb-16 md:pb-0' : '' }}">
                    @if (session(\App\Domain\Platform\Actions\StartUserImpersonation::SESSION_KEY))
                        <div class="col-span-full w-full border-b border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100">
                            <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    Estás usando la cuenta de <strong>{{ auth()->user()->name }}</strong> como soporte.
                                    Volverás a <strong>{{ data_get(session(\App\Domain\Platform\Actions\StartUserImpersonation::SESSION_KEY), 'impersonator_name', 'tu cuenta de superadmin') }}</strong> cuando cierres este modo.
                                </div>

                                <form method="POST" action="{{ route('impersonation.stop') }}">
                                    @csrf
                                    <flux:button type="submit" variant="filled" size="sm">
                                        Volver a mi cuenta
                                    </flux:button>
                                </form>
                            </div>
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </div>
        </div>

        @if (auth()->user()->experience === 'cliente')
            <x-cliente-bottom-nav />
        @endif

        <x-storefront-chat-widget
            :with-sound="false"
            character-gif="images/chatbot-merkamiga-IA.gif"
            character-frame1="images/chatbot-merkamiga-IA-frame1.png"
            :mode="auth()->user()->experience === 'emprendedor' ? 'emprendedor' : 'general'"
        />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @stack('scripts')
        @fluxScripts
    </body>
</html>
