@props([
    'wireModel',
    'value' => null,
    'label' => null,
    'max' => 5,
])

{{--
    Pedido del usuario: el resumen de "Opiniones de clientes" de la
    vitrina mostraba 5 estrellas fijas sin ningún dato real detrás.
    Selector reutilizable entre el formulario de opinión de la vitrina y
    el de recomendación de "Mis pedidos" — ambos alimentan el mismo
    promedio, así que comparten el mismo widget para no divergir.
--}}
<div>
    @if ($label)
        <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</span>
    @endif

    <div class="flex items-center gap-1">
        @for ($i = 1; $i <= $max; $i++)
            <button
                type="button"
                wire:click="$set('{{ $wireModel }}', {{ $i }})"
                class="{{ $i <= ($value ?? 0) ? 'text-amber-500' : 'text-zinc-300 dark:text-zinc-600' }} transition hover:text-amber-400"
                aria-label="{{ __(':n de :max estrellas', ['n' => $i, 'max' => $max]) }}"
            >
                <flux:icon.star class="size-6" :variant="$i <= ($value ?? 0) ? 'solid' : 'outline'" />
            </button>
        @endfor
    </div>

    @error($wireModel)
        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
