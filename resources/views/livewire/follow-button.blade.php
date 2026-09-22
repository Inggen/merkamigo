{{-- Ver el comentario en favorite-button.blade.php: raíz en un único <div> obligatoria para que wire:click funcione. --}}
<div>
    @if ($compact)
        <button
            type="button"
            wire:click="toggle"
            wire:loading.attr="disabled"
            title="{{ $following ? __('Dejar de seguir') : __('Seguir') }}"
            class="flex size-8 items-center justify-center rounded-full bg-white/90 text-zinc-500 shadow-sm backdrop-blur transition hover:text-brand-600 dark:bg-zinc-900/90 dark:text-zinc-300"
        >
            <flux:icon.bell class="size-4" :variant="$following ? 'solid' : 'outline'" :class="$following ? 'text-brand-600' : ''" />
        </button>
    @else
        <flux:button
            type="button"
            wire:click="toggle"
            wire:loading.attr="disabled"
            :variant="$following ? 'filled' : 'primary'"
            icon="{{ $following ? 'check' : 'plus' }}"
        >
            {{ $following ? __('Siguiendo') : __('Seguir') }}
        </flux:button>
    @endif
</div>
