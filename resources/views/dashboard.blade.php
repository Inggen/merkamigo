<x-layouts::app :title="__('Elige tu experiencia')">
    <div class="mx-auto flex h-full max-w-2xl flex-1 flex-col justify-center gap-6">
        <div class="text-center">
            <flux:heading size="xl">{{ __('¿Qué quieres hacer en Merkamigo?') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                {{ __('Puedes cambiar de experiencia cuando quieras desde tu cuenta.') }}
            </flux:text>
        </div>

        <x-experience-picker />
    </div>
</x-layouts::app>
