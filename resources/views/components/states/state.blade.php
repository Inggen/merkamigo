@props([
    'title',
    'description' => null,
])

{{--
    Base para los estados de interfaz exigidos por 0.2 del TODO (carga,
    vacío, error, sin conexión, permiso denegado, suspendido,
    mantenimiento). Los componentes concretos en components/states/*
    solo fijan el ícono (slot `icon`) y el texto por defecto.
--}}
<div {{ $attributes->class('flex flex-col items-center justify-center gap-3 rounded-xl border border-zinc-200 bg-white px-6 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900') }}>
    <div class="flex size-12 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-400">
        {{ $icon }}
    </div>

    <flux:heading size="lg">{{ $title }}</flux:heading>

    @if ($description)
        <flux:text class="max-w-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</flux:text>
    @endif

    @isset($slot)
        @if (trim($slot))
            <div class="mt-2">{{ $slot }}</div>
        @endif
    @endisset
</div>
