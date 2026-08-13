<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Avatar de la experiencia inmersiva')] class extends Component
{
    /**
     * Espeja en la cuenta la elección que `x-immersive.avatar-picker` ya
     * guardó en localStorage (fuente de verdad para "cómo me veo yo"). Solo
     * así, si este usuario es dueño de un negocio, la plaza inmersiva puede
     * mostrar una persona con su mismo preset junto a su stand — algo que
     * ningún otro visitante podría ver desde localStorage del dueño.
     */
    public function syncAvatarPreset(string $avatar): void
    {
        if (! in_array($avatar, ['hombre', 'mujer'], true)) {
            return;
        }

        auth()->user()->avatar_preset = $avatar;
        auth()->user()->save();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Avatar de la experiencia inmersiva') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Avatar')" :subheading="__('Elige cómo te ves al recorrer las plazas inmersivas')">
        {{-- `wire:ignore`: el picker es 100% autónomo (localStorage, sin
             estado de Livewire) — sin esto, el re-render que dispara
             `syncAvatarPreset()` al hacer clic borraba el `aria-pressed`
             que el propio script del picker le puso al botón, dejándolo
             visualmente "sin seleccionar" hasta recargar la página. --}}
        <div wire:ignore>
            <x-immersive.avatar-picker />
        </div>

        {{-- `x-pages::settings.layout` ya trae el menú completo de
             navegación de Ajustes (Perfil/Seguridad/Apariencia/Avatar) —
             usarlo una segunda vez para esta sección duplicaba todo ese
             menú (bug real reportado por el usuario). Mismo patrón que
             `security.blade.php` para sus sub-secciones: un `<section>`
             normal dentro del único layout de la página, no un segundo
             layout. --}}
        <section class="mt-12">
            <flux:heading>{{ __('Movimiento reducido') }}</flux:heading>
            <flux:subheading>{{ __('Apaga las animaciones decorativas (como el balanceo de otros personajes) al recorrer las plazas inmersivas') }}</flux:subheading>

            <div class="mt-6">
                <x-immersive.reduced-motion-toggle />
            </div>
        </section>
    </x-pages::settings.layout>

    @script
        <script>
            window.addEventListener('vpe-avatar-changed', (event) => {
                $wire.syncAvatarPreset(event.detail.avatar);
            });
        </script>
    @endscript
</section>
