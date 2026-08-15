{{--
    Botón de info con popover — pedido del usuario: el `title`/`aria-label`
    de un botón (tooltip nativo del navegador) no se notaba como que
    "funcionara" (aparece solo tras mantener el mouse quieto encima varios
    segundos, sin ninguna señal visual inmediata al pasar o hacer clic).
    Este componente reemplaza eso con un panel que se abre/cierra con clic,
    igual de accesible (el texto sigue en `aria-label` del botón) pero
    visible de una.
--}}
<div class="relative inline-flex" x-data="{ open: false }" @click.outside="open = false">
    <button
        type="button"
        @click.stop.prevent="open = ! open"
        aria-label="Más información"
        class="inline-flex items-center justify-center text-zinc-400 transition hover:text-zinc-600 dark:hover:text-zinc-200"
    >
        <x-filament::icon icon="heroicon-m-information-circle" class="h-4 w-4" />
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute right-0 top-full z-20 mt-2 w-64 rounded-lg border border-gray-200 bg-white p-3 text-xs leading-relaxed text-gray-600 shadow-lg dark:border-white/10 dark:bg-gray-800 dark:text-gray-300"
    >
        {{ $slot }}
    </div>
</div>
