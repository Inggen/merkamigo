@php
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Categorías')],
        ]),
        \App\Support\Seo\SchemaBuilder::itemList(
            $categories->map(fn ($category) => ['name' => $category->name])->all(),
            __('Categorías disponibles'),
        ),
    ];
@endphp

<x-layouts::public
    :title="__('Categorías')"
    :description="__('Conoce las categorías disponibles para explorar negocios, productos y servicios locales en Merkamigo.')"
    :canonical="route('categorias')"
    page-schema-type="CollectionPage"
    :schema-graph="$schemaGraph"
>
    <div class="mx-auto max-w-3xl px-6 py-10">
        <h1 class="mb-2 text-2xl font-semibold tracking-tight text-carbon dark:text-white">{{ __('Categorías') }}</h1>
        <flux:subheading class="mb-6">
            {{ __('Explora cada categoría y descubre en qué municipios ya hay negocios, productos y servicios publicados.') }}
        </flux:subheading>

        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($categories as $category)
                <a
                    href="{{ route('categorias.show', $category) }}"
                    class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-left transition hover:border-brand-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
                    wire:navigate
                >
                    <div class="font-medium text-carbon dark:text-white">{{ $category->name }}</div>
                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Ver municipios y negocios publicados en esta categoría') }}
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts::public>
