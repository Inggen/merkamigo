@props([
    'business',
    'href',
    'label' => __('Editar'),
    'compact' => false,
])

@if ($business?->isOwnedBy(auth()->user()))
    <flux:button
        :href="$href"
        wire:navigate
        size="sm"
        variant="ghost"
        icon="pencil-square"
        {{ $attributes->class($compact ? '!size-9 !p-0' : '') }}
        :aria-label="$label"
        :title="$label"
    >
        @unless ($compact)
            {{ $label }}
        @endunless
    </flux:button>
@endif
