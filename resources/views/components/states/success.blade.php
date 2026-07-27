@props([
    'title' => '¡Listo!',
    'description' => null,
])

<x-states.state :title="$title" :description="$description" {{ $attributes }}>
    <x-slot name="icon">
        <flux:icon.check-circle variant="outline" class="size-6" />
    </x-slot>

    {{ $slot }}
</x-states.state>
