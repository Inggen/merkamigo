@props([
    'sidebar' => false,
])

@if($sidebar)
    <a {{ $attributes->class('flex items-center gap-2') }}>
        <x-app-logo-icon class="h-9 w-auto" />
        <x-brand-wordmark size="base" />
    </a>
@else
    <flux:brand :name="config('app.name', 'Laravel')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center">
            <x-app-logo-icon class="size-8" />
        </x-slot>
    </flux:brand>
@endif
