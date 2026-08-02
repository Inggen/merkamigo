@props(['categories', 'activeCategory' => null, 'allUrl', 'urlFor'])

@php
    $visibleCategories = $categories->reject(fn ($category) => mb_strtolower($category->name) === 'otros');
@endphp

<nav
    aria-label="{{ __('Categorías') }}"
    class="grid grid-flow-col auto-cols-[10.5rem] gap-3 overflow-x-auto pb-2 sm:grid-flow-row sm:grid-cols-3 sm:overflow-visible lg:grid-cols-5 xl:grid-cols-6"
>
    <a
        href="{{ $allUrl }}"
        wire:navigate
        class="flex h-16 min-w-0 items-center gap-3 rounded-2xl border px-4 py-3 text-xs transition {{ ! $activeCategory ? 'border-brand-200 bg-brand-50 text-brand-700' : 'border-zinc-200 bg-white text-zinc-700 hover:border-brand-200 hover:bg-brand-50/60 hover:text-brand-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' }}"
        aria-label="{{ __('Ver todas las categorías') }}"
    >
        <span class="flex size-9 items-center justify-center rounded-xl {{ ! $activeCategory ? 'bg-white text-brand-600' : 'bg-zinc-50 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300' }}">
            <x-dynamic-component component="flux::icon.squares-2x2" class="size-5" variant="outline" />
        </span>
        <span class="line-clamp-2 text-left leading-tight font-medium">
            {{ __('Todas las categorías') }}
        </span>
    </a>

    @foreach ($visibleCategories as $category)
        <a
            href="{{ $urlFor($category) }}"
            wire:navigate
            class="flex h-16 min-w-0 items-center gap-3 rounded-2xl border px-4 py-3 text-xs transition {{ $activeCategory?->id === $category->id ? 'border-brand-200 bg-brand-50 text-brand-700' : 'border-zinc-200 bg-white text-zinc-700 hover:border-brand-200 hover:bg-brand-50/60 hover:text-brand-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' }}"
            aria-label="{{ __('Ver :category', ['category' => $category->name]) }}"
        >
            <span class="flex size-9 items-center justify-center rounded-xl {{ $activeCategory?->id === $category->id ? 'bg-white text-brand-600' : 'bg-zinc-50 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300' }}">
                <x-dynamic-component :component="'flux::icon.'.$category->icon" class="size-5" variant="outline" />
            </span>
            <span class="line-clamp-2 text-left leading-tight font-medium">
                {{ $category->name }}
            </span>
        </a>
    @endforeach
</nav>
