{{--
    Acceso inicial con dos caminos visibles (0.2 del TODO): "Quiero
    comprar/encontrar" y "Quiero vender/mostrar mi negocio". Se reutiliza
    tanto en la landing pública como en el selector del dashboard, para no
    duplicar la lógica de cambio de experiencia (SwitchExperience).
--}}
<div {{ $attributes->class('grid gap-4 sm:grid-cols-2') }}>
    <form method="POST" action="{{ route('experience.update') }}">
        @csrf
        <input type="hidden" name="experience" value="cliente">
        <button type="submit" class="group flex h-full w-full flex-col items-start gap-3 rounded-2xl border border-zinc-200 bg-white p-6 text-start shadow-xs transition hover:border-brand-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.magnifying-glass class="size-8 text-brand-600" variant="outline" />
            <flux:heading size="lg">{{ __('Quiero comprar/encontrar') }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                {{ __('Descubre negocios y productos cerca de ti.') }}
            </flux:text>
        </button>
    </form>

    <form method="POST" action="{{ route('experience.update') }}">
        @csrf
        <input type="hidden" name="experience" value="emprendedor">
        <button type="submit" class="group flex h-full w-full flex-col items-start gap-3 rounded-2xl border border-zinc-200 bg-white p-6 text-start shadow-xs transition hover:border-brand-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.building-storefront class="size-8 text-brand-600" variant="outline" />
            <flux:heading size="lg">{{ __('Quiero vender/mostrar mi negocio') }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                {{ __('Crea tu vitrina y recibe contactos por WhatsApp.') }}
            </flux:text>
        </button>
    </form>
</div>
