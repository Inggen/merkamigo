@props(['categories', 'activeCategory' => null, 'allUrl', 'urlFor'])

<nav aria-label="{{ __('Categorías') }}" class="flex gap-4 overflow-x-auto pb-2">
    <a href="{{ $allUrl }}" wire:navigate class="flex w-20 shrink-0 flex-col items-center gap-1.5 text-center" aria-label="{{ __('Ver todas las categorías') }}">
        <span class="flex size-14 items-center justify-center rounded-full border transition {{ ! $activeCategory ? 'border-brand-600 bg-brand-600 text-white' : 'border-zinc-200 bg-white text-zinc-500 hover:border-brand-300 hover:text-brand-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400' }}">
            <x-dynamic-component component="flux::icon.squares-2x2" class="size-6" variant="outline" />
        </span>
        <span class="text-xs leading-tight font-medium {{ ! $activeCategory ? 'text-brand-600' : 'text-zinc-600 dark:text-zinc-300' }}">
            {{ __('Todas') }}
        </span>
    </a>

    @foreach ($categories as $category)
        <a href="{{ $urlFor($category) }}" wire:navigate class="flex w-20 shrink-0 flex-col items-center gap-1.5 text-center" aria-label="{{ __('Ver :category', ['category' => $category->name]) }}">
            <span class="flex size-14 items-center justify-center rounded-full border transition {{ $activeCategory?->id === $category->id ? 'border-brand-600 bg-brand-600 text-white' : 'border-zinc-200 bg-white text-zinc-500 hover:border-brand-300 hover:text-brand-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400' }}">
                <x-dynamic-component :component="'flux::icon.'.$category->icon" class="size-6" variant="outline" />
            </span>
            <span class="text-xs leading-tight font-medium {{ $activeCategory?->id === $category->id ? 'text-brand-600' : 'text-zinc-600 dark:text-zinc-300' }}">
                {{ $category->name }}
            </span>
        </a>
    @endforeach
</nav>
