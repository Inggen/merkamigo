/**
 * Botón de engranaje en el header de la plaza inmersiva ("configuración
 * de pantalla"): reúne en un solo menú lo que antes vivía repartido en
 * dos lugares —
 *
 * 1. Calidad gráfica (Auto/Ligero/Equilibrado/Alto): el mismo
 *    `qualityControl` que ya maneja `immersive-quality.js`, cableado
 *    hasta ahora solo al panel técnico oculto de
 *    `immersive-perf-monitor.js` (`?perf=1` o tecla "P").
 * 2. Sensibilidad de cámara táctil: antes un engranaje aparte flotando
 *    junto al joystick (`VoxelPlazaEngine.buildTouchSensitivitySettings()`,
 *    ya retirado de `voxel-plaza-engine.js` — la mecánica de cámara/touch
 *    en sí NO se tocó, solo se movió el control a este menú). Sigue
 *    usando exactamente el mismo estado del motor (`engine.controls.touchSensitivity`)
 *    y el mismo `engine.persistTouchSensitivity()`, así que el valor
 *    guardado entre visitas no cambia. Solo tiene sentido en pantallas
 *    táctiles (no afecta la cámara con mouse), así que solo se agrega
 *    si el dispositivo es táctil.
 */
const QUALITY_TIER_OPTIONS = [
    { tier: null, label: 'Automática' },
    { tier: 'ligero', label: 'Ligero' },
    { tier: 'equilibrado', label: 'Equilibrado' },
    { tier: 'alto', label: 'Alto' },
];

export function attachDisplaySettingsPanel(qualityControl, { container = null, engine = null } = {}) {
    injectStylesOnce();

    const wrapper = document.createElement('div');
    wrapper.className = 'vpe-display-settings';

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'vpe-display-settings-toggle';
    toggle.setAttribute('aria-label', 'Configuración de pantalla');
    toggle.setAttribute('aria-haspopup', 'true');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.innerHTML = gearIcon();

    const menu = document.createElement('div');
    menu.className = 'vpe-display-settings-menu';
    menu.setAttribute('role', 'menu');
    menu.innerHTML = '<p class="vpe-display-settings-title">Calidad gráfica</p>';

    QUALITY_TIER_OPTIONS.forEach(({ tier, label }) => {
        const option = document.createElement('button');
        option.type = 'button';
        option.className = 'vpe-display-settings-option';
        option.setAttribute('role', 'menuitemradio');
        option.textContent = label;

        const isActive = tier === null
            ? !qualityControl.isOverride
            : (qualityControl.isOverride && tier === qualityControl.currentTier);
        option.classList.toggle('is-active', isActive);
        option.setAttribute('aria-checked', isActive ? 'true' : 'false');

        option.addEventListener('click', () => qualityControl.onSelect(tier));
        menu.appendChild(option);
    });

    const isTouchDevice = window.matchMedia('(hover: none) and (pointer: coarse)').matches;

    if (engine && isTouchDevice) {
        menu.appendChild(buildCameraSensitivitySection(engine));
    }

    wrapper.appendChild(toggle);
    wrapper.appendChild(menu);
    (container ?? document.body).appendChild(wrapper);

    function closeMenu() {
        wrapper.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        const isOpen = wrapper.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', (event) => {
        if (!wrapper.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });
}

function buildCameraSensitivitySection(engine) {
    const wrap = document.createElement('div');
    wrap.className = 'vpe-display-settings-slider-wrap';
    wrap.innerHTML = '<hr class="vpe-display-settings-divider"><p class="vpe-display-settings-title">Sensibilidad de cámara</p>';

    const slider = document.createElement('input');
    slider.type = 'range';
    slider.min = '0.4';
    slider.max = '2.2';
    slider.step = '0.05';
    slider.value = String(engine.controls.touchSensitivity);
    slider.className = 'vpe-display-settings-slider';
    slider.setAttribute('aria-label', 'Sensibilidad de cámara');

    slider.addEventListener('input', () => {
        const value = Number.parseFloat(slider.value);
        engine.controls.touchSensitivity = value;
        engine.persistTouchSensitivity(value);
    });

    // Evita que arrastrar la barra también arrastre/gire la cámara (mismo
    // resguardo que tenía el control original en `voxel-plaza-engine.js`).
    slider.addEventListener('pointerdown', (event) => event.stopPropagation());

    wrap.appendChild(slider);

    return wrap;
}

function gearIcon() {
    return `<svg class="vpe-display-settings-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
    </svg>`;
}

let stylesInjected = false;

function injectStylesOnce() {
    if (stylesInjected) {
        return;
    }

    stylesInjected = true;

    const style = document.createElement('style');
    style.textContent = `
        .vpe-display-settings { position: relative; }

        .vpe-display-settings-toggle {
            width: 38px; height: 38px; border-radius: 999px; border: none;
            display: flex; align-items: center; justify-content: center;
            background: transparent; color: #fff; cursor: pointer;
        }
        .vpe-display-settings-icon { width: 22px; height: 22px; }

        .vpe-display-settings-menu {
            display: none; position: absolute; top: calc(100% + 10px); right: 0;
            width: 200px; background: #fff; color: #1f2430; border-radius: 14px;
            padding: 10px; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
            font-family: inherit; z-index: 1;
        }
        .vpe-display-settings.is-open .vpe-display-settings-menu { display: block; }

        .vpe-display-settings-title {
            margin: 4px 8px 8px; font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.04em; color: #9aa0ac;
        }

        .vpe-display-settings-option {
            display: block; width: 100%; text-align: left; padding: 9px 10px;
            border-radius: 9px; border: none; background: transparent;
            color: #1f2430; font-size: 0.86rem; font-weight: 600; cursor: pointer;
        }
        .vpe-display-settings-option:hover { background: #f5f5f7; }
        .vpe-display-settings-option.is-active { background: #d7352a; color: #fff; }

        .vpe-display-settings-divider { border: none; border-top: 1px solid #eceef1; margin: 8px 4px; }
        .vpe-display-settings-slider-wrap { padding: 0 4px 4px; }
        .vpe-display-settings-slider {
            touch-action: pan-x; display: block; width: 100%; margin: 6px 0 4px;
            accent-color: #d7352a;
        }
    `;
    document.head.appendChild(style);
}
