@php
    $pageTitle = __('Categoría: :category', ['category' => $category->name]);
    $pageDescription = __('Explora municipios, negocios, productos y servicios publicados en la categoría :category dentro de Merkamigo.', ['category' => $category->name]);
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Categorías'), 'url' => route('categorias')],
            ['name' => $category->name],
        ]),
        \App\Support\Seo\SchemaBuilder::itemList(
            $municipalities->map(fn ($municipality) => [
                'name' => $municipality->name,
                'url' => route('buscar', ['municipio' => $municipality->slug, 'categoria' => $category->slug]),
            ])->all(),
            __('Municipios con :category', ['category' => $category->name]),
        ),
    ];
@endphp

<x-layouts::public
    :title="$pageTitle"
    :description="$pageDescription"
    :canonical="route('categorias.show', $category)"
    page-schema-type="CollectionPage"
    :page-schema-data="['about' => $category->name]"
    :schema-graph="$schemaGraph"
>
    <div class="mx-auto max-w-5xl px-6 py-10">
        <nav class="mb-4 flex flex-wrap items-center gap-1 text-sm text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('home') }}" class="hover:text-brand-600" wire:navigate>{{ __('Inicio') }}</a>
            <span>/</span>
            <a href="{{ route('categorias') }}" class="hover:text-brand-600" wire:navigate>{{ __('Categorías') }}</a>
            <span>/</span>
            <span class="text-zinc-700 dark:text-zinc-200">{{ $category->name }}</span>
        </nav>

        <flux:heading size="xl" class="mb-2">{{ $category->name }}</flux:heading>
        <flux:subheading class="mb-8 max-w-3xl">
            {{ __('Encuentra en qué municipios ya hay oferta activa para esta categoría y entra directo a la plaza local correspondiente.') }}
        </flux:subheading>

        @if ($municipalities->isEmpty())
            <x-states.empty
                title="{{ __('Todavía no hay negocios publicados en esta categoría') }}"
                description="{{ __('Vuelve pronto o explora otras categorías mientras nuevos negocios se publican.') }}"
            />
        @else
            <section class="mb-10">
                <h2 class="mb-4 text-lg font-semibold text-carbon dark:text-white">{{ __('Municipios con oferta activa') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($municipalities as $municipality)
                        <a
                            href="{{ route('buscar', ['municipio' => $municipality->slug, 'categoria' => $category->slug]) }}"
                            class="rounded-2xl border border-zinc-200 bg-white p-4 transition hover:border-brand-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
                            wire:navigate
                        >
                            <div class="font-medium text-carbon dark:text-white">{{ $municipality->name }}</div>
                            <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $municipality->department }}</div>
                            <div class="mt-3 text-sm text-brand-700 dark:text-brand-300">
                                {{ trans_choice(':count negocio publicado|:count negocios publicados', $municipality->published_businesses_count, ['count' => $municipality->published_businesses_count]) }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($featuredBusinesses->isNotEmpty())
            <section>
                <h2 class="mb-4 text-lg font-semibold text-carbon dark:text-white">{{ __('Negocios recientes en esta categoría') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featuredBusinesses as $business)
                        <x-business-card :business="$business" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts::public>
