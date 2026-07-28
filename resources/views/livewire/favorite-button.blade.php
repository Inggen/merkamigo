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
        variant="ghost"
        :icon="$favorited ? 'heart' : 'heart'"
        :class="$favorited ? 'text-brand-600' : ''"
    >
        {{ $favorited ? __('Guardado') : __('Guardar') }}
    </flux:button>
@endif
