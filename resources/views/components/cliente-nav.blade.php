@php
    use App\Domain\Discovery\Models\Municipality;

    $currentMunicipio = request()->cookie('municipio')
        ? Municipality::where('slug', request()->cookie('municipio'))->where('is_active', true)->first()
        : null;
    $allMunicipios = Municipality::where('is_active', true)->orderBy('name')->get();
@endphp

<header class="sticky top-0 z-30 border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-3 px-6 py-3">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2" wire:navigate>
            <x-app-logo-icon class="h-8 w-auto" />
            <span class="hidden font-heading text-lg font-semibold sm:inline">Merkamigo</span>
        </a>

        <flux:dropdown class="shrink-0">
            <flux:button size="sm" variant="ghost" icon="map-pin" icon-trailing="chevron-down">
                {{ $currentMunicipio?->name ?? __('Elegir municipio') }}
            </flux:button>
            <flux:menu>
                @foreach ($allMunicipios as $option)
                    <form method="POST" action="{{ route('clientes.municipio') }}">
                        @csrf
                        <input type="hidden" name="municipality_id" value="{{ $option->id }}">
                        <flux:menu.item as="button" type="submit" :icon="$currentMunicipio?->id === $option->id ? 'check' : null" class="w-full cursor-pointer">
                            {{ $option->name }}
                        </flux:menu.item>
                    </form>
                @endforeach
            </flux:menu>
        </flux:dropdown>

        <form method="GET" action="{{ route('buscar') }}" class="order-last w-full sm:order-none sm:w-auto sm:flex-1">
            <flux:input
                name="q"
                :value="request()->get('q')"
                placeholder="{{ __('Buscar negocios, productos o servicios...') }}"
                icon="magnifying-glass"
            />
            @if ($currentMunicipio)
                <input type="hidden" name="municipio" value="{{ $currentMunicipio->id }}">
            @endif
        </form>

        <nav class="ml-auto flex shrink-0 items-center gap-1">
            <flux:button size="sm" variant="ghost" icon="magnifying-glass" :href="route('buscar')" wire:navigate>
                <span class="hidden md:inline">{{ __('Explorar') }}</span>
            </flux:button>

            @auth
                <flux:button size="sm" variant="ghost" icon="heart" :href="route('clientes.favoritos')" wire:navigate>
                    <span class="hidden md:inline">{{ __('Favoritos') }}</span>
                </flux:button>

                <flux:dropdown position="bottom" align="end">
                    <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                    <flux:menu>
                        <flux:menu.item :href="route('dashboard')" icon="squares-2x2" wire:navigate>{{ __('Mi cuenta') }}</flux:menu.item>
                        <flux:menu.separator />
                        <x-experience-switch-menu />
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                                {{ __('Cerrar sesión') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @else
                <flux:button size="sm" variant="ghost" :href="route('login')" wire:navigate>{{ __('Iniciar sesión') }}</flux:button>
                <flux:button size="sm" variant="primary" :href="route('register')" wire:navigate>{{ __('Crear cuenta') }}</flux:button>
            @endauth
        </nav>
    </div>
</header>
