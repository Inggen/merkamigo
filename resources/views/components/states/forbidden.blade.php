@props([
    'title' => 'No tienes permiso para ver esto',
    'description' => 'Si crees que es un error, contacta a quien administra este negocio.',
])

<x-states.state :title="$title" :description="$description" {{ $attributes }}>
    <x-slot name="icon">
        <flux:icon.lock-closed variant="outline" class="size-6" />
    </x-slot>

    {{ $slot }}
</x-states.state>
