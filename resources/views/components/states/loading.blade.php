@props([
    'label' => 'Cargando…',
])

<div {{ $attributes->class('flex items-center justify-center gap-3 py-12 text-zinc-500 dark:text-zinc-400') }}>
    <flux:icon.loading class="size-5" />
    <span>{{ $label }}</span>
</div>
