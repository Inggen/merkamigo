@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'canonical' => null,
    'robots' => null,
    'pageSchemaType' => 'WebPage',
    'pageSchemaData' => [],
    'schemaGraph' => [],
    'ogType' => 'website',
    'showMunicipalitySelector' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', [
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'canonical' => $canonical,
            'robots' => $robots,
            'pageSchemaType' => $pageSchemaType,
            'pageSchemaData' => $pageSchemaData,
            'schemaGraph' => $schemaGraph,
            'ogType' => $ogType,
        ])
    </head>
    <body class="min-h-screen bg-mist dark:bg-zinc-900 dark:text-white">
        <x-cliente-nav :show-municipality-selector="$showMunicipalitySelector" />

        <main class="{{ auth()->check() && auth()->user()->experience === 'cliente' ? 'pb-16 md:pb-0' : '' }}">
            {{ $slot }}
        </main>

        @include('partials.public-footer')

        @auth
            @if (auth()->user()->experience === 'cliente')
                <x-cliente-bottom-nav />
            @endif
        @endauth

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @stack('scripts')
        @fluxScripts
    </body>
</html>
