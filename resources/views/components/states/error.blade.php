@props([
    'title' => 'Algo salió mal',
    'description' => 'Intenta de nuevo en un momento. Si el problema sigue, contacta a soporte.',
])

<x-states.state :title="$title" :description="$description" {{ $attributes }}>
    <x-slot name="icon">
        <flux:icon.exclamation-triangle variant="outline" class="size-6" />
    </x-slot>

    {{ $slot }}
</x-states.state>
