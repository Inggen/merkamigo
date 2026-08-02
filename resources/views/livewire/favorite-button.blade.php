{{--
    Envolver todo en un único <div> raíz es obligatorio aquí: cuando el
    componente empieza directamente con `@if ($compact)`, Livewire compila
    ese `@if` como un comentario HTML en la misma línea que la etiqueta
    siguiente (sin salto de línea), y su detector de "elemento raíz" busca
    la primera etiqueta que sí empiece en una línea nueva — eso hacía que
    `wire:id`/`wire:snapshot` terminaran en un hijo interno (el ícono o el
    indicador de carga) en vez del propio botón, y por eso el clic nunca
    llegaba a disparar `toggle()`.
--}}
<div>
    @if ($compact)
        <button
            type="button"
            wire:click="toggle"
            wire:loading.attr="disabled"
            title="{{ $favorited ? __('Quitar de favoritos') : __('Guardar en favoritos') }}"
            class="flex size-8 items-center justify-center rounded-full bg-white/90 text-zinc-500 shadow-sm backdrop-blur transition hover:text-brand-600 dark:bg-zinc-900/90 dark:text-zinc-300"
        >
            <flux:icon.heart class="size-4" :variant="$favorited ? 'solid' : 'outline'" :class="$favorited ? 'text-brand-600' : ''" />
        </button>
    @else
        <flux:button
            type="button"
            wire:click="toggle"
            wire:loading.attr="disabled"
            variant="ghost"
            icon="heart"
            :icon:variant="$favorited ? 'solid' : 'outline'"
            :class="$favorited ? 'text-brand-600' : ''"
        >
            {{ $favorited ? __('Guardado') : __('Guardar') }}
        </flux:button>
    @endif
</div>
