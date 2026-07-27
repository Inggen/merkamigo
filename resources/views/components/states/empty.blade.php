@props([
    'title' => 'Todavía no hay nada aquí',
    'description' => null,
])

<x-states.state :title="$title" :description="$description" {{ $attributes }}>
    <x-slot name="icon">
        <flux:icon.inbox variant="outline" class="size-6" />
    </x-slot>

    {{ $slot }}
</x-states.state>
