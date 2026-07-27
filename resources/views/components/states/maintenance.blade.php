@props([
    'title' => 'Estamos en mantenimiento',
    'description' => 'Volvemos en un momento. Gracias por tu paciencia.',
])

<x-states.state :title="$title" :description="$description" {{ $attributes }}>
    <x-slot name="icon">
        <flux:icon.wrench-screwdriver variant="outline" class="size-6" />
    </x-slot>

    {{ $slot }}
</x-states.state>
