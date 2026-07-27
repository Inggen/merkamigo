@props([
    'title' => 'Sin conexión',
    'description' => 'No pudimos conectarnos. Revisa tu internet e intenta de nuevo.',
])

<x-states.state :title="$title" :description="$description" {{ $attributes }}>
    <x-slot name="icon">
        <flux:icon.signal-slash variant="outline" class="size-6" />
    </x-slot>

    {{ $slot }}
</x-states.state>
