{{--
    Selector de experiencia (0.2.1 del TODO): cambia entre Cliente y
    Emprendedor sin cerrar sesión ni duplicar cuenta. Compartido entre el
    menú de escritorio y el de móvil para no duplicar la lógica.
--}}
{{--
    Los dos `<form>` viven fuera de la lista y se asocian a sus botones
    por `form="id"` (soportado en todos los navegadores modernos) — así
    los `<flux:menu.item>` quedan como hijos directos de `<flux:menu>`,
    igual que "Mi cuenta"/"Favoritos". Envolver cada uno en su propio
    `<form>` (como se hacía antes) rompía esa relación de hijo directo y
    el submenú se veía como dos tarjetas sueltas en vez de una sola lista
    continua.
--}}
<form id="experience-switch-cliente" method="POST" action="{{ route('experience.update') }}" class="hidden">
    @csrf
    <input type="hidden" name="experience" value="cliente">
</form>
<form id="experience-switch-emprendedor" method="POST" action="{{ route('experience.update') }}" class="hidden">
    @csrf
    <input type="hidden" name="experience" value="emprendedor">
</form>

<flux:menu.submenu heading="{{ __('Cambiar de experiencia') }}" icon="arrows-right-left">
    <flux:menu.item as="button" type="submit" form="experience-switch-cliente" class="w-full cursor-pointer">
        <span class="me-1 inline-flex w-4 shrink-0 items-center justify-center text-zinc-400 dark:text-white/60">
            @if (auth()->user()->experience === 'cliente')
                <flux:icon.check variant="mini" class="size-4" />
            @endif
        </span>
        <span>{{ __('Cliente') }}</span>
    </flux:menu.item>

    <flux:menu.item as="button" type="submit" form="experience-switch-emprendedor" class="w-full cursor-pointer">
        <span class="me-1 inline-flex w-4 shrink-0 items-center justify-center text-zinc-400 dark:text-white/60">
            @if (auth()->user()->experience === 'emprendedor')
                <flux:icon.check variant="mini" class="size-4" />
            @endif
        </span>
        <span>{{ __('Emprendedor') }}</span>
    </flux:menu.item>
</flux:menu.submenu>
