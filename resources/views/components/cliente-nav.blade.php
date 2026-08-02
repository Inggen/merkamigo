@props([
    'showMunicipalitySelector' => true,
])

@php
    use App\Domain\Discovery\Models\Municipality;

    $currentMunicipio = request()->cookie('municipio')
        ? Municipality::where('slug', request()->cookie('municipio'))->where('is_active', true)->first()
        : null;
    $allMunicipios = Municipality::where('is_active', true)->orderBy('name')->get();
    $guestLoginUrl = route('login');
    $guestFavoritesMessage = __('Necesitas ingresar o crear una cuenta para guardar favoritos.');
    $guestNeedsMessage = __('Necesitas ingresar o crear una cuenta para publicar en Pídelo.');
@endphp

<header
    class="sticky top-0 z-30 border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800"
    style="box-shadow: 0 0 15px rgba(0, 0, 0, .2);"
>
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-3 px-6 py-3">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" wire:navigate>
            <x-app-logo-icon class="h-10 w-auto" />
            <x-brand-wordmark size="lg" class="hidden sm:inline" />
        </a>

        @if ($showMunicipalitySelector)
            <flux:dropdown class="shrink-0">
                <flux:button size="sm" variant="ghost" icon="map-pin" icon-trailing="chevron-down">
                    {{ $currentMunicipio?->name ?? __('Todos') }}
                </flux:button>
                <flux:menu>
                    <form method="POST" action="{{ route('clientes.municipio') }}">
                        @csrf
                        <input type="hidden" name="municipality_id" value="">
                        <flux:menu.item as="button" type="submit" :icon="is_null($currentMunicipio) ? 'check' : null" class="w-full cursor-pointer">
                            {{ __('Todos') }}
                        </flux:menu.item>
                    </form>

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
        @endif

        <nav class="ml-auto flex shrink-0 items-center gap-1">
            @auth
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="clipboard-document-list"
                    :href="route('mis-solicitudes')"
                    wire:navigate
                    class="[&_[data-flux-icon]]:text-brand-600"
                >
                    <span class="hidden md:inline">{{ __('Mis solicitudes') }}</span>
                </flux:button>

                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="shopping-bag"
                    :href="route('pidelo.nueva')"
                    wire:navigate
                    class="[&_[data-flux-icon]]:text-brand-600"
                >
                    <span class="hidden md:inline">{{ __('Pídelo') }}</span>
                </flux:button>

                <flux:dropdown position="bottom" align="end">
                    <flux:profile
                        :avatar="auth()->user()->avatarUrl()"
                        :initials="auth()->user()->initials()"
                        circle
                        :chevron="false"
                    />
                    <flux:menu>
                        <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>{{ __('Mi cuenta') }}</flux:menu.item>
                        <flux:menu.item :href="route('clientes.favoritos')" icon="heart" wire:navigate>{{ __('Favoritos') }}</flux:menu.item>
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
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="shopping-bag"
                    x-data
                    x-on:click.prevent="$flux.toast({ text: '{{ e($guestNeedsMessage) }}' }); setTimeout(() => window.location.href = '{{ $guestLoginUrl }}', 900)"
                    class="[&_[data-flux-icon]]:text-brand-600"
                >
                    {{ __('Pídelo') }}
                </flux:button>
                <flux:button
                    size="sm"
                    variant="ghost"
                    icon="heart"
                    x-data
                    x-on:click.prevent="$flux.toast({ text: '{{ e($guestFavoritesMessage) }}' }); setTimeout(() => window.location.href = '{{ $guestLoginUrl }}', 900)"
                    class="[&_[data-flux-icon]]:text-brand-600"
                >
                    {{ __('Favoritos') }}
                </flux:button>
                <flux:button
                    size="sm"
                    variant="ghost"
                    :href="route('login')"
                    wire:navigate
                    class="rounded-xl border border-brand-300 px-4 text-brand-700 hover:border-brand-400 hover:bg-brand-50"
                >
                    {{ __('Ingresa') }}
                </flux:button>
                <flux:button size="sm" variant="primary" :href="route('emprendedores.bienvenida')" wire:navigate class="rounded-xl px-4">
                    {{ __('Publica tu negocio') }}
                </flux:button>
            @endauth
        </nav>
    </div>
</header>
