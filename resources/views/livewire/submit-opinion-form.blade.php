{{-- Envolver todo en un único <div> raíz — ver el mismo comentario en
     favorite-button.blade.php sobre por qué un @if de primer nivel rompe
     la detección del elemento raíz de Livewire. --}}
<div class="mb-4 rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
    @guest
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Inicia sesión para dejar tu opinión sobre este negocio.') }}
            </flux:text>
            <flux:button size="sm" :href="route('login')" wire:navigate>
                {{ __('Iniciar sesión') }}
            </flux:button>
        </div>
    @else
        @if ($hasSubmitted)
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Ya enviaste tu opinión sobre este negocio. Aparecerá aquí una vez sea moderada.') }}
            </flux:text>
        @else
            <div class="space-y-3">
                <flux:heading size="sm">{{ __('Deja tu opinión') }}</flux:heading>

                <x-star-rating-picker wire-model="rating" :value="$rating" :label="__('Tu calificación')" />

                <flux:textarea
                    wire:model="body"
                    :label="__('¿Qué tal tu experiencia con este negocio?')"
                    rows="3"
                    maxlength="500"
                    required
                />

                <flux:checkbox.group wire:model="tags" :label="__('Etiquetas (opcional)')">
                    @foreach (\App\Domain\Trust\Models\Recommendation::SUGGESTED_TAGS as $tag)
                        <flux:checkbox value="{{ $tag }}" :label="$tag" />
                    @endforeach
                </flux:checkbox.group>

                <flux:button size="sm" variant="primary" wire:click="submit">
                    {{ __('Enviar opinión') }}
                </flux:button>
            </div>
        @endif
    @endguest
</div>
