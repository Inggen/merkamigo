@php
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Cómo funciona')],
        ]),
    ];
@endphp

<x-layouts::public
    :title="__('Cómo funciona')"
    :description="__('Descubre cómo funciona Merkamigo para emprendedores y compradores: vitrinas, productos, servicios y contacto por WhatsApp.')"
    :canonical="route('como-funciona')"
    :schema-graph="$schemaGraph"
>
    <div class="mx-auto max-w-3xl px-6 py-10">
        <h1 class="mb-6 text-2xl font-semibold tracking-tight text-carbon dark:text-white">{{ __('Cómo funciona Merkamigo') }}</h1>

        <div class="space-y-8">
            <div>
                <flux:heading size="lg">{{ __('Para emprendedores') }}</flux:heading>
                <ol class="mt-2 list-inside list-decimal space-y-1 text-zinc-600 dark:text-zinc-300">
                    <li>{{ __('Crea tu vitrina en cinco minutos: nombre, descripción, fotos y WhatsApp.') }}</li>
                    <li>{{ __('Agrega tus productos o servicios.') }}</li>
                    <li>{{ __('Publica y comparte tu enlace o código QR.') }}</li>
                    <li>{{ __('Recibe contactos directo por WhatsApp.') }}</li>
                </ol>
            </div>

            <div>
                <flux:heading size="lg">{{ __('Para compradores') }}</flux:heading>
                <ol class="mt-2 list-inside list-decimal space-y-1 text-zinc-600 dark:text-zinc-300">
                    <li>{{ __('Explora la plaza de tu municipio, sin necesidad de crear cuenta.') }}</li>
                    <li>{{ __('Encuentra negocios y productos cerca de ti.') }}</li>
                    <li>{{ __('Contacta directo por WhatsApp.') }}</li>
                </ol>
            </div>

            <flux:text class="text-sm text-zinc-500">
                {{ __('Merkamigo no procesa pagos ni domicilios: conecta compradores y negocios, el acuerdo lo hacen directamente por WhatsApp.') }}
            </flux:text>

            <flux:link :href="route('preguntas-frecuentes')" wire:navigate>
                {{ __('Ver preguntas frecuentes →') }}
            </flux:link>
        </div>
    </div>
</x-layouts::public>
