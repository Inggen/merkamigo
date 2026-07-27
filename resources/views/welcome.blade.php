<x-layouts::public>
    <div class="mx-auto flex max-w-4xl flex-col items-center gap-4 px-6 pt-16 pb-12 text-center">
        <flux:heading size="xl" class="text-4xl">{{ __('Descubre lo local, conecta con tu comunidad') }}</flux:heading>
        <flux:text class="max-w-xl text-lg text-zinc-500 dark:text-zinc-400">
            {{ __('Merkamigo conecta emprendedores locales con compradores cercanos. Crea tu vitrina o descubre negocios de tu municipio.') }}
        </flux:text>
    </div>

    <div class="mx-auto max-w-3xl px-6 pb-20">
        <x-experience-picker />
    </div>
</x-layouts::public>
