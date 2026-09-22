<div>
    <button
        type="button"
        wire:click="toggle"
        wire:loading.attr="disabled"
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-sm font-medium text-zinc-500 transition hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 {{ $reacted ? 'text-brand-600 dark:text-brand-400' : '' }}"
    >
        <flux:icon.heart class="size-4" :variant="$reacted ? 'solid' : 'outline'" />
        <span>{{ $count > 0 ? $count : __('Me gusta') }}</span>
    </button>
</div>
