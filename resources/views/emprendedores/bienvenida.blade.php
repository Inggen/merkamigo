<x-layouts::public :title="__('Crea tu Merkamigo')">
    <div class="mx-auto flex max-w-3xl flex-col items-center gap-6 px-6 py-16 text-center">
        <flux:heading size="xl">{{ __('Crea tu vitrina digital en cinco minutos') }}</flux:heading>
        <flux:text class="max-w-xl text-lg text-zinc-500 dark:text-zinc-400">
            {{ __('Muestra tu negocio, sé encontrado por compradores cerca de ti y recibe contactos directo por WhatsApp.') }}
        </flux:text>

        <flux:button variant="primary" :href="route('register')" wire:navigate>
            {{ __('Crear mi vitrina') }}
        </flux:button>

        <flux:text class="text-sm text-zinc-400">
            {{ __('¿Ya tienes cuenta?') }}
            <a href="{{ route('login') }}" class="font-medium text-brand-600" wire:navigate>{{ __('Inicia sesión') }}</a>
        </flux:text>
    </div>
</x-layouts::public>
