@props([
    'title' => 'Este contenido está suspendido',
    'description' => 'Un moderador suspendió temporalmente este contenido mientras se revisa.',
])

<x-states.state :title="$title" :description="$description" {{ $attributes }}>
    <x-slot name="icon">
        <flux:icon.no-symbol variant="outline" class="size-6" />
    </x-slot>

    {{ $slot }}
</x-states.state>
