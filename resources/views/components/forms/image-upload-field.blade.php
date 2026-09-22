@props([
    'title' => null,
    'previewUrl' => null,
    'previewUrls' => [],
    'previewAlt' => null,
    'error' => null,
    'hint' => 'Selecciona una imagen JPG, PNG o WEBP.',
    'previewClass' => 'h-64 w-full rounded-xl object-cover',
    'removeAction' => null,
])

@php($hasPreview = filled($previewUrl) || $previewUrls !== [])

<div class="space-y-3">
    @if ($title)
        <div class="space-y-1">
            <flux:text class="font-medium text-zinc-800">{{ $title }}</flux:text>
            @unless ($hasPreview)
                <flux:text class="text-sm text-zinc-500">{{ __($hint) }}</flux:text>
            @endunless
        </div>
    @endif

    @if ($previewUrl)
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-2 shadow-sm">
            <img src="{{ $previewUrl }}" class="{{ $previewClass }}" alt="{{ $previewAlt }}">

            @if ($removeAction)
                <button
                    type="button"
                    wire:click="{{ $removeAction }}"
                    wire:loading.attr="disabled"
                    wire:target="{{ $removeAction }}"
                    aria-label="{{ __('Eliminar imagen') }}"
                    title="{{ __('Eliminar imagen') }}"
                    class="absolute right-4 top-4 inline-flex size-9 items-center justify-center rounded-full bg-zinc-900 text-xl leading-none text-white shadow-md transition hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-60"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            @endif
        </div>
    @elseif ($previewUrls !== [])
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($previewUrls as $index => $url)
                <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-2 shadow-sm">
                    <img src="{{ $url }}" class="aspect-square h-full w-full rounded-xl object-cover" alt="{{ __('Vista previa de la foto') }}">

                    @if ($removeAction)
                        <button
                            type="button"
                            wire:click="{{ $removeAction }}({{ (int) $index }})"
                            wire:loading.attr="disabled"
                            wire:target="{{ $removeAction }}"
                            aria-label="{{ __('Eliminar imagen') }}"
                            title="{{ __('Eliminar imagen') }}"
                            class="absolute right-4 top-4 inline-flex size-9 items-center justify-center rounded-full bg-zinc-900 text-xl leading-none text-white shadow-md transition hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-60"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <label class="block cursor-pointer rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/90 p-4 transition hover:border-brand-400 hover:bg-white">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-zinc-800">{{ __('Adjuntar imagen') }}</div>
                    <div class="text-xs text-zinc-500">{{ __('Haz clic aquí para elegir un archivo desde tu dispositivo.') }}</div>
                </div>

                <span class="inline-flex items-center rounded-xl bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow-sm">
                    {{ __('Seleccionar archivo') }}
                </span>
            </div>

            <input
                type="file"
                {{ $attributes->merge([
                    'class' => 'mt-4 block w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100',
                ]) }}
            >
        </label>
    @endif

    @if ($error)
        <flux:text class="text-sm text-red-600">{{ $error }}</flux:text>
    @endif
</div>
