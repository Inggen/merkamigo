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

        <div class="mt-6 grid w-full gap-6 border-t border-zinc-200 pt-10 text-left sm:grid-cols-3 dark:border-zinc-700">
            <div class="flex flex-col items-center gap-2 text-center sm:items-start sm:text-left">
                <div class="flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-950">
                    <flux:icon.map-pin class="size-5" variant="outline" />
                </div>
                <flux:heading size="sm">{{ __('Cercanía') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Conecta con lo que tienes cerca.') }}</flux:text>
            </div>

            <div class="flex flex-col items-center gap-2 text-center sm:items-start sm:text-left">
                <div class="flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-950">
                    <flux:icon.user-group class="size-5" variant="outline" />
                </div>
                <flux:heading size="sm">{{ __('Comunidad') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Apoya negocios locales y genera impacto.') }}</flux:text>
            </div>

            <div class="flex flex-col items-center gap-2 text-center sm:items-start sm:text-left">
                <div class="flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-950">
                    <flux:icon.eye class="size-5" variant="outline" />
                </div>
                <flux:heading size="sm">{{ __('Visibilidad') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Haz visible tu negocio ante más personas.') }}</flux:text>
            </div>
        </div>
    </div>
</x-layouts::public>
