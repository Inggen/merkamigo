<x-layouts::app :title="__('Inicio')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <flux:heading size="xl">{{ __('Hola, :name', ['name' => auth()->user()->name]) }}</flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            {{ __('Esta es tu experiencia como Emprendedor en Merkamigo.') }}
        </flux:text>

        @if ($businesses->isEmpty())
            <x-states.empty
                title="Todavía no tienes ninguna vitrina"
                description="Crea tu Merkamigo en cinco minutos y empieza a recibir contactos por WhatsApp."
            >
                <flux:button variant="primary" :href="route('emprendedores.crear-vitrina')" wire:navigate>
                    {{ __('Crear mi vitrina') }}
                </flux:button>
            </x-states.empty>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($businesses as $business)
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:heading>{{ $business->name }}</flux:heading>
                        <flux:badge class="mt-1" size="sm">{{ $business->status }}</flux:badge>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts::app>
