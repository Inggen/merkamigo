@props([
    'showMunicipalitySelector' => true,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title ?? null, 'description' => $description ?? null, 'image' => $image ?? null])
    </head>
    <body class="min-h-screen bg-mist dark:bg-zinc-900 dark:text-white">
        <x-cliente-nav :show-municipality-selector="$showMunicipalitySelector" />

        <main>
            {{ $slot }}
        </main>

        @include('partials.public-footer')

        @stack('scripts')
        @fluxScripts
    </body>
</html>
