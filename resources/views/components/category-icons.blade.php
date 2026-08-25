@props(['categories', 'activeCategory' => null, 'allUrl', 'urlFor', 'visibleCount' => 8])

@php
    $visibleCategories = $categories->reject(fn ($category) => mb_strtolower($category->name) === 'otros');
    $inlineCategories = $visibleCategories->take($visibleCount);
    $overflowCategories = $visibleCategories->slice($visibleCount);
    // Pedido del usuario: el borde con sombra envuelve TODA la fila (una
    // sola tarjeta), no cada botón por separado — y los íconos siempre
    // llevan el color de marca, estén activos o no.
    $tileClasses = fn (bool $active) => $active
        ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300'
        : 'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-800';
@endphp

<nav
    aria-label="{{ __('Categorías') }}"
    class="flex justify-between gap-3 overflow-x-auto rounded-xl bg-white p-3 shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
>
    <a
        href="{{ $allUrl }}"
        wire:navigate
        class="flex h-24 w-24 shrink-0 flex-col items-center rounded-xl shadow-sm justify-center gap-2 rounded-2xl px-2 py-3 text-center text-xs transition {{ $tileClasses(! $activeCategory) }}"
        aria-label="{{ __('Ver todas las categorías') }}"
    >
        <x-dynamic-component component="flux::icon.squares-2x2" class="size-5 text-brand-600 dark:text-brand-400" variant="outline" />
        <span class="line-clamp-2 leading-tight font-medium">
            {{ __('Todas las categorías') }}
        </span>
    </a>

    @foreach ($inlineCategories as $category)
        <a
            href="{{ $urlFor($category) }}"
            wire:navigate
            class="flex h-24 w-24 shrink-0 flex-col items-center rounded-xl shadow-sm justify-center gap-2 rounded-2xl px-2 py-3 text-center text-xs transition {{ $tileClasses($activeCategory?->id === $category->id) }}"
            aria-label="{{ __('Ver :category', ['category' => $category->name]) }}"
        >
            <x-dynamic-component :component="'flux::icon.'.$category->icon" class="size-5 text-brand-600 dark:text-brand-400" variant="outline" />
            <span class="line-clamp-2 leading-tight font-medium">
                {{ $category->name }}
            </span>
        </a>
    @endforeach

    {{--
        Pedido del usuario: "Ver más" siempre debe estar, no solo cuando
        sobran categorías por el límite `visibleCount` — con overflow abre
        un desplegable con las que no caben; sin overflow, es un enlace
        directo a la página completa de categorías.
    --}}
    @if ($overflowCategories->isNotEmpty())
        <flux:dropdown class="shrink-0">
            <button
                type="button"
                class="flex h-24 w-24 shrink-0 flex-col items-center rounded-xl shadow-sm justify-center gap-2 rounded-2xl px-2 py-3 text-center text-xs transition {{ $tileClasses($overflowCategories->contains(fn ($category) => $activeCategory?->id === $category->id)) }}"
                aria-label="{{ __('Ver más categorías') }}"
            >
                <x-dynamic-component component="flux::icon.ellipsis-horizontal" class="size-5 text-brand-600 dark:text-brand-400" variant="outline" />
                <span class="font-medium">{{ __('Ver más') }}</span>
            </button>

            <flux:menu>
                @foreach ($overflowCategories as $category)
                    <flux:menu.item :href="$urlFor($category)" wire:navigate>
                        {{ $category->name }}
                    </flux:menu.item>
                @endforeach

                <flux:menu.separator />

                <flux:menu.item :href="route('categorias')" wire:navigate>
                    {{ __('Ver todas las categorías') }}
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    @else
        <a
            href="{{ route('categorias') }}"
            wire:navigate
            class="flex h-24 w-24 shrink-0 flex-col items-center shadow-sm justify-center gap-2 rounded-2xl px-2 py-3 text-center text-xs transition {{ $tileClasses(false) }}"
            aria-label="{{ __('Ver más categorías') }}"
        >
            <x-dynamic-component component="flux::icon.ellipsis-horizontal" class="size-5 text-brand-600 dark:text-brand-400" variant="outline" />
            <span class="font-medium">{{ __('Ver más') }}</span>
        </a>
    @endif
</nav>
