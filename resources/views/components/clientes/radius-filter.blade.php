@props([
    'radiusKm' => null,
])

{{--
    Radio de "Cerca de mí" (Fase 5.1 del TODO social) — a diferencia del
    propio botón "Cerca de mí" (que solo ordena, nunca excluye), elegir un
    radio aquí SÍ filtra: solo se ven negocios/productos dentro de esa
    distancia. Solo tiene sentido junto a "Cerca de mí" activo, por eso el
    padre lo muestra condicionado a `$near`.
--}}
@php
    $presets = ['1', '3', '5', '10'];
    $isCustom = $radiusKm !== null && ! in_array((string) $radiusKm, $presets, true);
@endphp

<div
    x-data="{
        choice: @js($isCustom ? 'custom' : ($radiusKm !== null ? (string) $radiusKm : '')),
        custom: @js($isCustom ? (string) $radiusKm : ''),
    }"
    class="flex shrink-0 items-center gap-2"
>
    <input type="hidden" name="radio_km" x-bind:value="choice === 'custom' ? custom : choice">

    <flux:select
        x-model="choice"
        x-on:change="if (choice !== 'custom') $el.closest('form').submit()"
        size="sm"
        class="w-36"
    >
        <flux:select.option value="">{{ __('Sin límite') }}</flux:select.option>
        <flux:select.option value="1">{{ __('1 km') }}</flux:select.option>
        <flux:select.option value="3">{{ __('3 km') }}</flux:select.option>
        <flux:select.option value="5">{{ __('5 km') }}</flux:select.option>
        <flux:select.option value="10">{{ __('10 km') }}</flux:select.option>
        <flux:select.option value="custom">{{ __('Personalizado') }}</flux:select.option>
    </flux:select>

    <template x-if="choice === 'custom'">
        <div class="flex items-center gap-1">
            <input
                type="number"
                min="0.5"
                max="200"
                step="0.5"
                x-model="custom"
                placeholder="{{ __('Km') }}"
                class="w-20 rounded-lg border-zinc-300 text-sm dark:border-zinc-600 dark:bg-zinc-800"
            >
            <flux:button type="submit" size="sm">{{ __('Aplicar') }}</flux:button>
        </div>
    </template>
</div>
