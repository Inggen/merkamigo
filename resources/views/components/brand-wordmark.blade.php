@props([
    'size' => 'base',
    'contrast' => 'default',
])

@php
    $classes = match ($size) {
        'lg' => 'text-xl sm:text-2xl',
        'sm' => 'text-base',
        default => 'text-lg',
    };

    $migoClasses = $contrast === 'inverse' ? 'text-white' : 'text-zinc-900 dark:text-white';
@endphp

<span {{ $attributes->class(['font-heading font-bold tracking-tight leading-none', $classes]) }}>
    <span class="text-brand-500">Merka</span><span class="{{ $migoClasses }}">migo</span>
</span>
