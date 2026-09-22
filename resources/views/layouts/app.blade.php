<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main :class="auth()->user()->experience === 'emprendedor' ? 'entrepreneur-main' : ''">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
