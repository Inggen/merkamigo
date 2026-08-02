{{--
    Selector de experiencia (0.2.1 del TODO): cambia entre Cliente y
    Emprendedor sin cerrar sesión ni duplicar cuenta. Compartido entre el
    menú de escritorio y el de móvil para no duplicar la lógica.
--}}
<flux:menu.submenu heading="{{ __('Cambiar de experiencia') }}" icon="arrows-right-left">
    <form method="POST" action="{{ route('experience.update') }}">
        @csrf
        <input type="hidden" name="experience" value="cliente">
        <flux:menu.item as="button" type="submit" class="w-full cursor-pointer">
            <span class="me-1 inline-flex w-4 shrink-0 items-center justify-center text-zinc-400 dark:text-white/60">
                @if (auth()->user()->experience === 'cliente')
                    <flux:icon.check variant="mini" class="size-4" />
                @endif
            </span>
            <span>{{ __('Cliente') }}</span>
        </flux:menu.item>
    </form>

    <form method="POST" action="{{ route('experience.update') }}">
        @csrf
        <input type="hidden" name="experience" value="emprendedor">
        <flux:menu.item as="button" type="submit" class="w-full cursor-pointer">
            <span class="me-1 inline-flex w-4 shrink-0 items-center justify-center text-zinc-400 dark:text-white/60">
                @if (auth()->user()->experience === 'emprendedor')
                    <flux:icon.check variant="mini" class="size-4" />
                @endif
            </span>
            <span>{{ __('Emprendedor') }}</span>
        </flux:menu.item>
    </form>
</flux:menu.submenu>
