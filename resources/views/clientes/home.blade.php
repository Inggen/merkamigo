<x-layouts::app :title="__('Inicio')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <flux:heading size="xl">{{ __('Hola, :name', ['name' => auth()->user()->name]) }}</flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            {{ __('Esta es tu experiencia como Cliente en Merkamigo.') }}
        </flux:text>

        <x-states.empty
            title="Explorar y Pídelo en Merkamigo llegan en la próxima fase"
            description="Aquí verás la plaza de tu municipio, negocios destacados y tus solicitudes."
        />
    </div>
</x-layouts::app>
