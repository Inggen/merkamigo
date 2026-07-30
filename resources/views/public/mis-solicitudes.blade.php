<x-layouts::cliente :title="__('Mis solicitudes')">
    <div class="mx-auto max-w-3xl px-6 py-8">
        <div class="mb-6 flex items-center justify-between">
            <flux:heading size="xl">{{ __('Mis solicitudes') }}</flux:heading>
            <flux:button variant="primary" size="sm" :href="route('pidelo.nueva')" wire:navigate>
                {{ __('Nueva solicitud') }}
            </flux:button>
        </div>

        @if ($needs->isEmpty())
            <x-states.empty
                title="{{ __('Todavía no has publicado ninguna solicitud') }}"
                description="{{ __('Cuenta qué necesitas y recibe propuestas de negocios cercanos.') }}"
            >
                <flux:button variant="primary" :href="route('pidelo.nueva')" wire:navigate>{{ __('Publicar lo que necesito') }}</flux:button>
            </x-states.empty>
        @else
            <div class="space-y-3">
                @foreach ($needs as $need)
                    <a href="{{ route('mis-solicitudes.show', $need) }}" wire:navigate class="block rounded-2xl border border-zinc-200 bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <flux:heading size="lg">{{ $need->title }}</flux:heading>
                                <div class="mt-1 flex flex-wrap items-center gap-x-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if ($need->category)
                                        <span>{{ $need->category->name }}</span>
                                        <span>·</span>
                                    @endif
                                    <span>{{ trans_choice(':count propuesta|:count propuestas', $need->offers_count, ['count' => $need->offers_count]) }}</span>
                                </div>
                            </div>
                            <flux:badge :color="match ($need->status) {
                                \App\Domain\Needs\Models\Need::BORRADOR => 'zinc',
                                \App\Domain\Needs\Models\Need::PUBLICADA, \App\Domain\Needs\Models\Need::RECIBIENDO_OFERTAS => 'green',
                                \App\Domain\Needs\Models\Need::SELECCIONADA => 'blue',
                                \App\Domain\Needs\Models\Need::CERRADA => 'zinc',
                                default => 'red',
                            }">
                                {{ match ($need->status) {
                                    \App\Domain\Needs\Models\Need::BORRADOR => __('Borrador'),
                                    \App\Domain\Needs\Models\Need::PUBLICADA => __('Publicada'),
                                    \App\Domain\Needs\Models\Need::RECIBIENDO_OFERTAS => __('Recibiendo propuestas'),
                                    \App\Domain\Needs\Models\Need::SELECCIONADA => __('Seleccionada'),
                                    \App\Domain\Needs\Models\Need::CERRADA => __('Cerrada'),
                                    \App\Domain\Needs\Models\Need::VENCIDA => __('Vencida'),
                                    \App\Domain\Needs\Models\Need::CANCELADA => __('Cancelada'),
                                    default => $need->status,
                                } }}
                            </flux:badge>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts::cliente>
