@php
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Municipios')],
        ]),
        \App\Support\Seo\SchemaBuilder::itemList(
            $municipalities->map(fn ($municipality) => [
                'name' => $municipality->name,
                'url' => route('buscar', ['municipio' => $municipality->slug]),
            ])->all(),
            __('Municipios activos'),
        ),
    ];
@endphp

<x-layouts::public
    :title="__('Municipios')"
    :description="__('Explora los municipios activos de Merkamigo en Bogotá y Sabana Norte.')"
    :canonical="route('municipios')"
    page-schema-type="CollectionPage"
    :schema-graph="$schemaGraph"
>
    <div class="mx-auto max-w-3xl px-6 py-10">
        <h1 class="mb-2 text-2xl font-semibold tracking-tight text-carbon dark:text-white">{{ __('Municipios activos') }}</h1>
        <flux:subheading class="mb-6">{{ __('Merkamigo ya está disponible en Bogotá y municipios activos de Sabana Norte.') }}</flux:subheading>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($municipalities as $municipality)
                <a href="{{ route('buscar', ['municipio' => $municipality->slug]) }}" class="rounded-xl border border-zinc-200 p-4 hover:border-brand-300 dark:border-zinc-700" wire:navigate>
                    <flux:heading>{{ $municipality->name }}</flux:heading>
                    <flux:text class="text-zinc-500">{{ $municipality->department }}</flux:text>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts::public>
