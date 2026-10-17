@props(['checks'])
<details class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
    <summary class="cursor-pointer font-semibold">{{ __('Tus productos en Google: configuración y ayuda') }}</summary>
    <div class="mt-4 space-y-4">
        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Merkamigo reutiliza los datos de tu vitrina y catálogo. Tener local físico, aparecer en Google Maps y tener inventario local aprobado son pasos distintos.') }}</p>
        <ul class="space-y-3">
            @foreach ($checks as $check)
                <li class="text-sm">
                    <span class="font-medium">{{ $check['ready'] ? __('Configurado') : __('Por revisar') }} · {{ __($check['label']) }}</span>
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __($check['help']) }}</p>
                </li>
            @endforeach
        </ul>
        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Mientras el inventario local no esté preparado, Merkamigo excluye las fichas de tiendas físicas y mantiene el catálogo online. Automático: ID del producto, enlace, fotos, nombre, descripción, precio, ofertas y disponibilidad. No hace falta copiarlos a Merchant Center. Los servicios no se envían como productos físicos.') }}</p>
        <div class="flex flex-wrap gap-3 text-sm">
            <a class="underline" href="https://support.google.com/merchants/answer/13869896?hl=es" target="_blank" rel="noopener noreferrer">{{ __('Cómo obtener el código de tienda') }}</a>
            <a class="underline" href="https://support.google.com/merchants/answer/160161?hl=es" target="_blank" rel="noopener noreferrer">{{ __('Qué poner en GTIN, MPN y marca') }}</a>
            <a class="underline" href="{{ route('soporte.solicitud.crear') }}">{{ __('Solicitar ayuda para vincular mi tienda') }}</a>
        </div>
        {{ $slot }}
    </div>
</details>
