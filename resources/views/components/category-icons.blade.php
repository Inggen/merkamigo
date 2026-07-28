@props(['categories', 'activeCategory' => null, 'allUrl', 'urlFor'])

<div class="flex gap-3 overflow-x-auto pb-1">
    <a
        href="{{ $allUrl }}"
        wire:navigate
        class="flex shrink-0 flex-col items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-medium transition {{ ! $activeCategory ? 'bg-brand-600 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' }}"
    >
        <x-dynamic-component component="flux::icon.squares-2x2" class="size-5" variant="outline" />
        {{ __('Todas') }}
    </a>

    @foreach ($categories as $category)
        <a
            href="{{ $urlFor($category) }}"
            wire:navigate
            class="flex shrink-0 flex-col items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-medium transition {{ $activeCategory?->id === $category->id ? 'bg-brand-600 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' }}"
        >
            <x-dynamic-component :component="'flux::icon.'.$category->icon" class="size-5" variant="outline" />
            <span class="max-w-20 truncate">{{ $category->name }}</span>
        </a>
    @endforeach
</div>
