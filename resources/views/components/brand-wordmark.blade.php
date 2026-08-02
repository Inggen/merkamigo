@props([
    'size' => 'base',
])

@php
    $classes = match ($size) {
        'lg' => 'text-xl sm:text-2xl',
        'sm' => 'text-base',
        default => 'text-lg',
    };
@endphp

<span {{ $attributes->class(['font-heading font-bold tracking-tight leading-none', $classes]) }}>
    <span class="text-brand-500">Merka</span><span class="text-zinc-900 dark:text-white">migo</span>
</span>
