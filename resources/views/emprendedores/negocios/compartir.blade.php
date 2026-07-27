<x-layouts::app :title="__('Compartir')">
    <div class="mx-auto w-full max-w-lg space-y-6 text-center">
        <flux:heading size="xl">{{ __('Comparte tu vitrina') }}</flux:heading>

        @if ($business->isPublished())
            <img src="{{ route('vitrinas.qr', $business) }}" alt="QR" class="mx-auto size-48 rounded-xl border border-zinc-200 dark:border-zinc-700">

            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:text class="break-all">{{ route('vitrinas.show', $business) }}</flux:text>
            </div>

            <div class="flex justify-center gap-3">
                <flux:button variant="primary" :href="route('vitrinas.show', $business)" target="_blank">
                    {{ __('Ver mi vitrina') }}
                </flux:button>
                <flux:button variant="ghost" :href="route('vitrinas.qr', $business)" target="_blank">
                    {{ __('Descargar QR') }}
                </flux:button>
            </div>
        @else
            <x-states.empty
                title="Publica tu vitrina para poder compartirla"
                description="El enlace y el código QR solo funcionan una vez que tu negocio está publicado."
            >
                <flux:button variant="primary" :href="route('emprendedores.negocios.vitrina', $business)" wire:navigate>
                    {{ __('Ir a mi vitrina') }}
                </flux:button>
            </x-states.empty>
        @endif
    </div>
</x-layouts::app>
