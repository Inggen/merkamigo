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
        <x-immersive.avatar-picker />
    </x-pages::settings.layout>

    <script>
        window.addEventListener('vpe-avatar-changed', (event) => {
            $wire.syncAvatarPreset(event.detail.avatar);
        });
    </script>
</section>
