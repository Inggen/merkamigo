<x-layouts::app :title="__('Elige tu experiencia')">
    <div class="mx-auto flex min-h-full w-full max-w-5xl flex-1 flex-col justify-center gap-10 py-8 lg:py-12">
        <div class="text-center">
            <span class="mx-auto mb-5 block h-1 w-14 rounded-full bg-brand-500 shadow-[0_0_18px_rgba(239,68,68,0.65)]"></span>
            <h1 class="text-3xl font-black tracking-tight text-zinc-950 sm:text-4xl lg:text-5xl dark:text-white">
                {{ __('¿Qué quieres hacer en') }} <span class="text-brand-500">{{ __('Merkamigo?') }}</span>
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-base text-zinc-500 sm:text-lg dark:text-zinc-400">
                {{ __('Elige cómo quieres comenzar. Podrás cambiar de experiencia cuando quieras desde tu cuenta.') }}
            </p>
        </div>

        <x-experience-picker />
    </div>
</x-layouts::app>
