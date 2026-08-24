@php($knowledge = $this->knowledge())

<x-filament-panels::page>
    @if ($knowledge->hasDocument())
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center gap-3">
                <x-filament::icon icon="heroicon-o-document-check" class="size-6 text-success-500" />
                <div>
                    <p class="text-sm font-medium text-zinc-950 dark:text-white">{{ $knowledge->document_original_name }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Actualizado :fecha', ['fecha' => $knowledge->updated_at?->format('d/m/Y H:i')]) }}</p>
                </div>
            </div>

            <x-filament::button color="danger" variant="ghost" wire:click="remove" wire:confirm="{{ __('¿Quitar este documento del contexto del asistente?') }}">
                {{ __('Quitar documento') }}
            </x-filament::button>
        </div>
    @endif

    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                {{ __('Guardar') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
