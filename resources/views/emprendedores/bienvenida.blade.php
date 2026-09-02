@php
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Crear vitrina')],
        ]),
    ];
@endphp

<x-layouts::public
    :title="__('Crear una vitrina digital')"
    :description="__('Crea gratis tu vitrina digital en Merkamigo, publica productos o servicios y recibe contactos por WhatsApp.')"
    :canonical="route('emprendedores.bienvenida')"
    :schema-graph="$schemaGraph"
    :show-municipality-selector="false"
>
    @php
        $heroBackground = $municipality?->coverUrl() ?? asset('images/backgrounds/fondo-buscador-principal.webp');
    @endphp

    <div
        class="relative overflow-hidden bg-cover bg-center px-6 py-16 text-white"
        style="background-image: url('{{ $heroBackground }}')"
    >
        <div class="absolute inset-0 bg-gradient-to-t from-brand-950/85 via-brand-900/60 to-brand-900/30"></div>

        <div class="relative mx-auto flex max-w-3xl flex-col items-center gap-6 text-center">
            <h1 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">{{ __('Crea tu vitrina digital en cinco minutos') }}</h1>
            <flux:text class="max-w-xl text-lg text-brand-100">
                {{ __('Muestra tu negocio, sé encontrado por compradores cerca de ti y recibe contactos directo por WhatsApp.') }}
            </flux:text>

            <flux:button variant="primary" :href="url('/emprendedores')" wire:navigate>
                {{ __('Crear mi vitrina') }}
            </flux:button>


        </div>
    </div>

    <div class="mx-auto max-w-6xl px-6 py-10">
        <div class="grid w-full gap-6 text-left sm:grid-cols-3">
            <div class="flex flex-col items-center gap-2 text-center sm:items-center sm:text-center">
                <div class="flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-950">
                    <flux:icon.map-pin class="size-5" variant="outline" />
                </div>
                <flux:heading size="sm">{{ __('Cercanía') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Conecta con lo que tienes cerca.') }}</flux:text>
            </div>

            <div class="flex flex-col items-center gap-2 text-center sm:items-center sm:text-center">
                <div class="flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-950">
                    <flux:icon.user-group class="size-5" variant="outline" />
                </div>
                <flux:heading size="sm">{{ __('Comunidad') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Apoya negocios locales y genera impacto.') }}</flux:text>
            </div>

            <div class="flex flex-col items-center gap-2 text-center sm:items-center sm:text-center">
                <div class="flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-950">
                    <flux:icon.eye class="size-5" variant="outline" />
                </div>
                <flux:heading size="sm">{{ __('Visibilidad') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Haz visible tu negocio ante más personas.') }}</flux:text>
            </div>
        </div>
    </div>
</x-layouts::public>
