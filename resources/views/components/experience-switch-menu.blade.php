{{-- Selector compartido para cambiar de experiencia sin cerrar sesión. --}}
<div
    class="px-2 py-1.5"
    role="group"
    aria-label="{{ __('Experiencia de Merkamigo') }}"
>
    <div class="grid grid-cols-2 gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800">
        @foreach ([
            ['value' => 'cliente', 'icon' => 'user', 'label' => __('Cliente')],
            ['value' => 'emprendedor', 'icon' => 'building-storefront', 'label' => __('Emprendedor')],
        ] as $option)
            @php($isActive = auth()->user()->experience === $option['value'])

            <form method="POST" action="{{ route('experience.update') }}" class="min-w-0">
                @csrf
                <input type="hidden" name="experience" value="{{ $option['value'] }}">

                <button
                    type="submit"
                    @class([
                        'flex h-10 w-full min-w-0 items-center justify-center gap-1.5 rounded-lg px-2 text-xs font-semibold transition',
                        'bg-white text-brand-600 shadow-sm dark:bg-zinc-700 dark:text-brand-300' => $isActive,
                        'text-zinc-500 hover:bg-white/70 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-700/70 dark:hover:text-white' => ! $isActive,
                    ])
                    aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                    title="{{ __('Cambiar a :experience', ['experience' => $option['label']]) }}"
                >
                    <x-dynamic-component :component="'flux::icon.'.$option['icon']" class="size-4 shrink-0" variant="outline" />
                    <span class="truncate">{{ $option['label'] }}</span>
                </button>
            </form>
        @endforeach
    </div>
</div>
