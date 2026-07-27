{{--
    Selector de experiencia (0.2.1 del TODO): cambia entre Cliente y
    Emprendedor sin cerrar sesión ni duplicar cuenta. Compartido entre el
    menú de escritorio y el de móvil para no duplicar la lógica.
--}}
<flux:menu.submenu heading="{{ __('Cambiar de experiencia') }}" icon="arrows-right-left">
    <form method="POST" action="{{ route('experience.update') }}">
        @csrf
        <input type="hidden" name="experience" value="cliente">
        <flux:menu.item as="button" type="submit" icon="{{ auth()->user()->experience === 'cliente' ? 'check' : null }}" class="w-full cursor-pointer">
            {{ __('Cliente') }}
        </flux:menu.item>
    </form>

    <form method="POST" action="{{ route('experience.update') }}">
        @csrf
        <input type="hidden" name="experience" value="emprendedor">
        <flux:menu.item as="button" type="submit" icon="{{ auth()->user()->experience === 'emprendedor' ? 'check' : null }}" class="w-full cursor-pointer">
            {{ __('Emprendedor') }}
        </flux:menu.item>
    </form>
</flux:menu.submenu>
