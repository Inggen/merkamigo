<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title ?? null, 'description' => $description ?? null, 'image' => $image ?? null])
    </head>
    <body class="min-h-screen bg-mist dark:bg-zinc-900 dark:text-white">
        <header class="border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
                    <x-app-logo-icon class="h-9 w-auto" />
                    <span class="font-heading text-lg font-semibold">Merkamigo</span>
                </a>

                <nav class="flex items-center gap-3 text-sm">
                    @auth
                        <flux:button :href="route('dashboard')" variant="primary" size="sm" wire:navigate>
                            {{ __('Ir a mi cuenta') }}
                        </flux:button>
                    @else
                        <flux:button :href="route('login')" variant="ghost" size="sm" wire:navigate>
                            {{ __('Iniciar sesión') }}
                        </flux:button>
                        <flux:button :href="route('register')" variant="primary" size="sm" wire:navigate>
                            {{ __('Crear cuenta') }}
                        </flux:button>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        @include('partials.public-footer')

        @fluxScripts
    </body>
</html>
