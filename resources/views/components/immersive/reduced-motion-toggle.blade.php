{{--
    Selector de movimiento reducido (IMM-042): apaga animaciones puramente
    decorativas del motor (balanceo idle de NPCs). Sin lógica de servidor —
    lee y escribe directamente la misma clave de localStorage
    (`vpe-reduced-motion`) que usa `reduced-motion-preference.js` al
    construir el motor 3D, mismo patrón exacto que
    `x-immersive.avatar-picker` (JS plano, funciona tanto en labs sin
    Alpine como en Ajustes con Livewire).
--}}
@props(['class' => ''])

<div data-reduced-motion-toggle role="group" aria-label="Movimiento reducido" class="reduced-motion-toggle {{ $class }}">
    <button type="button" data-reduced-motion-option="false" class="reduced-motion-option">Normal</button>
    <button type="button" data-reduced-motion-option="true" class="reduced-motion-option">Movimiento reducido</button>
</div>

<style>
    .reduced-motion-toggle {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .reduced-motion-toggle .reduced-motion-option {
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

    .reduced-motion-toggle .reduced-motion-option[aria-pressed="true"] {
        background: #d7352a;
        border-color: #d7352a;
        color: #fff;
        opacity: 1;
    }
</style>

<script type="module">
    import { loadReducedMotionPreference, saveReducedMotionPreference } from '{{ asset('js/lib/reduced-motion-preference.js') }}';

    document.querySelectorAll('[data-reduced-motion-toggle]:not([data-reduced-motion-toggle-bound])').forEach((root) => {
        root.dataset.reducedMotionToggleBound = '1';

        const buttons = root.querySelectorAll('[data-reduced-motion-option]');
        const applySelection = (enabled) => {
            buttons.forEach((button) => {
                button.setAttribute('aria-pressed', String(button.dataset.reducedMotionOption === String(enabled)));
            });
        };

        applySelection(loadReducedMotionPreference());

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const enabled = button.dataset.reducedMotionOption === 'true';
                saveReducedMotionPreference(enabled);
                applySelection(enabled);
            });
        });
    });
</script>
