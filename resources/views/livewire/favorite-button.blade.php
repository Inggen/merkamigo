<flux:button
    type="button"
    wire:click="toggle"
    variant="ghost"
    :icon="$favorited ? 'heart' : 'heart'"
    :class="$favorited ? 'text-brand-600' : ''"
>
    {{ $favorited ? __('Guardado') : __('Guardar') }}
</flux:button>
