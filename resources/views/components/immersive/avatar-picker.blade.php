{{--
    Selector de avatar (IMM-030): Hombre/Mujer. Sin lógica de servidor —
    lee y escribe directamente la misma clave de localStorage
    (`vpe-avatar`) que usa `avatar-preference.js` al construir el motor
    3D, así que este mismo componente sirve tanto para "elegir antes de
    entrar" (labs/*-inmersiva.blade.php, páginas estáticas sin
    Alpine/Livewire) como para "cambiar desde Ajustes" (Livewire/Volt).
    Por eso usa JS plano en vez de Alpine: las páginas de labs no cargan
    Alpine, y este control debe funcionar igual en ambos contextos.
--}}
@props(['class' => ''])

<div data-avatar-picker role="group" aria-label="Elegir avatar" class="avatar-picker {{ $class }}">
    <button type="button" data-avatar-option="hombre" class="avatar-picker-option">Hombre</button>
    <button type="button" data-avatar-option="mujer" class="avatar-picker-option">Mujer</button>
</div>

<style>
    .avatar-picker {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .avatar-picker .avatar-picker-option {
        padding: 6px 16px;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
        border: 1px solid currentColor;
        background: transparent;
        color: inherit;
        opacity: 0.55;
        cursor: pointer;
        transition: background 160ms ease, opacity 160ms ease, color 160ms ease;
    }

    .avatar-picker .avatar-picker-option[aria-pressed="true"] {
        background: #d7352a;
        border-color: #d7352a;
        color: #fff;
        opacity: 1;
    }
</style>

<script type="module">
    import { loadAvatarPreference, saveAvatarPreference } from '{{ asset('js/lib/avatar-preference.js') }}';

    document.querySelectorAll('[data-avatar-picker]:not([data-avatar-picker-bound])').forEach((root) => {
        root.dataset.avatarPickerBound = '1';

        const buttons = root.querySelectorAll('[data-avatar-option]');
        const applySelection = (key) => {
            buttons.forEach((button) => {
                button.setAttribute('aria-pressed', String(button.dataset.avatarOption === key));
            });
        };

        applySelection(loadAvatarPreference());

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                saveAvatarPreference(button.dataset.avatarOption);
                applySelection(button.dataset.avatarOption);
                // Aditivo: los consumidores actuales (páginas de labs, sin
                // Livewire) no escuchan este evento y lo ignoran sin efecto.
                // Settings > Avatar sí lo escucha para espejar la elección
                // en la cuenta (ver ⚡avatar.blade.php).
                window.dispatchEvent(new CustomEvent('vpe-avatar-changed', {
                    detail: { avatar: button.dataset.avatarOption },
                }));
            });
        });
    });
</script>
