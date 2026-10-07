<div
    x-data
    class="px-2 py-1.5"
    role="group"
    aria-label="{{ __('Tema de la interfaz') }}"
>
    <div class="grid grid-cols-3 gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800">
        @foreach ([
            ['value' => 'light', 'icon' => 'sun', 'label' => __('Tema claro')],
            ['value' => 'dark', 'icon' => 'moon', 'label' => __('Tema oscuro')],
            ['value' => 'system', 'icon' => 'computer-desktop', 'label' => __('Usar tema del sistema')],
        ] as $option)
            <button
                type="button"
                x-on:click.stop="$flux.appearance = '{{ $option['value'] }}'"
                x-bind:aria-pressed="$flux.appearance === '{{ $option['value'] }}'"
                x-bind:class="$flux.appearance === '{{ $option['value'] }}'
                    ? 'bg-white text-brand-600 shadow-sm dark:bg-zinc-700 dark:text-brand-300'
                    : 'text-zinc-500 hover:bg-white/70 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-700/70 dark:hover:text-white'"
                class="flex h-10 items-center justify-center rounded-lg transition"
                aria-label="{{ $option['label'] }}"
                title="{{ $option['label'] }}"
            >
                <x-dynamic-component :component="'flux::icon.'.$option['icon']" class="size-5" variant="outline" />
                <span class="sr-only">{{ $option['label'] }}</span>
            </button>
        @endforeach
    </div>
</div>
