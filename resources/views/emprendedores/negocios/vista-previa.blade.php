<x-layouts::app :title="__('Vista previa')">
    <div class="mx-auto w-full max-w-2xl space-y-6">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __('Vista previa') }}</flux:heading>
            <flux:button variant="ghost" :href="route('emprendedores.negocios.vitrina', $business)" wire:navigate>
                {{ __('Editar información') }}
            </flux:button>
        </div>

        <x-storefront-preview :business="$business" />
    </div>
</x-layouts::app>
