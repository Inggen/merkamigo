<div class="space-y-3">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Miniatura de referencia cargada para este objeto — para comparar rápido contra las cajas del editor sin salir de esta vista.
    </p>

    @if ($template->thumbnailUrl())
        <img
            src="{{ $template->thumbnailUrl() }}"
            alt="Miniatura de {{ $template->name }}"
            class="mx-auto max-h-[70vh] w-auto rounded-lg border border-gray-200 object-contain dark:border-white/10"
        />
    @else
        <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
            Este objeto todavía no tiene una imagen cargada en el campo "Miniatura".
        </div>
    @endif
</div>
