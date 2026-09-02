@php
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Pídelo')],
        ]),
    ];
@endphp

<x-layouts::cliente
    :title="__('Pídelo en Merkamigo')"
    :description="__('Publica lo que necesitas y recibe propuestas de negocios locales cerca de ti en Merkamigo.')"
    :canonical="route('pidelo')"
    :schema-graph="$schemaGraph"
>
    <div class="mx-auto max-w-3xl px-6 py-8">
        <div class="mb-8 flex flex-col items-start gap-4 rounded-2xl border border-brand-100 bg-brand-50 p-6 sm:flex-row sm:items-center sm:justify-between dark:border-brand-900 dark:bg-brand-950">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-carbon dark:text-white">{{ __('Pídelo en Merkamigo') }}</h1>
                <flux:text class="mt-1 text-zinc-600 dark:text-zinc-300">
                    {{ __('Cuenta qué necesitas y recibe propuestas de negocios cerca de ti.') }}
                </flux:text>
            </div>

            <flux:button variant="primary" :href="route('pidelo.nueva')" wire:navigate>
                {{ __('Publicar lo que necesito') }}
            </flux:button>
        </div>

        @if (! $municipality)
            <x-states.empty
                title="{{ __('Elige tu municipio para ver solicitudes') }}"
                description="{{ __('Selecciona un municipio desde el encabezado para ver lo que otros compradores están pidiendo cerca de ti.') }}"
            />
        @elseif ($needs->isEmpty())
            <x-states.empty
                title="{{ __('Todavía no hay solicitudes en :municipio', ['municipio' => $municipality->name]) }}"
                description="{{ __('Sé la primera persona en pedir algo en tu municipio.') }}"
            />
        @else
            <div class="space-y-4">
                @foreach ($needs as $need)
                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <flux:heading size="lg">{{ $need->title }}</flux:heading>
                                <div class="mt-1 flex flex-wrap items-center gap-x-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if ($need->category)
                                        <span>{{ $need->category->name }}</span>
                                        <span>·</span>
                                    @endif
                                    <span>{{ __('Publicada :fecha', ['fecha' => $need->published_at?->diffForHumans()]) }}</span>
                                    <span>·</span>
                                    <span>{{ trans_choice(':count propuesta|:count propuestas', $need->offers_count, ['count' => $need->offers_count]) }}</span>
                                </div>
                            </div>
                        </div>

                        <flux:text class="mt-3 line-clamp-2 text-zinc-600 dark:text-zinc-300">
                            {{ $need->description }}
                        </flux:text>

                        <div class="mt-3">
                            <flux:link :href="route('pidelo.show', $need)" wire:navigate class="text-sm font-medium">{{ __('Ver solicitud →') }}</flux:link>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $needs->links() }}
            </div>
        @endif
    </div>
</x-layouts::cliente>
