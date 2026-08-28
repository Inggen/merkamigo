/**
 * IMM-032: indicador "Ver vitrina" al acercarse a un stand ocupado.
 * Construido encima del motor sin tocar la mecánica del personaje — usa
 * el punto de extensión público `engine.onUpdate()` (corre cada frame,
 * después de `updatePlayer`/`updateCamera`, ver
 * docs/architecture/personaje-inmersivo.md) en vez de modificar
 * `updatePlayer`/`updateCamera`/`bindInput`.
 *
 * Recibe el registro que ya devuelve `loadDynamicStands()`
 * (`dynamic-stand-loader.js`): `[{ position: THREE.Vector3, business, root }]`.
 * Ignora cualquier stand con `root.visible === false` — un stand oculto
 * por `stand-search-panel.js` (filtro/búsqueda) no debe poder activar el
 * indicador "Ver vitrina" aunque el personaje pase por su posición.
 */
import { THREE } from './voxel-plaza-engine.js?v=6';

const DEFAULT_RADIUS = 6;
const DEFAULT_HOLD_MS = 220;
const AUTO_OPEN_MS = 1500;

export function attachStandProximity(engine, stands, { radius = DEFAULT_RADIUS, holdMs = DEFAULT_HOLD_MS, onOpen = null } = {}) {
    if (!stands?.length) {
        return () => {};
    }

    const indicator = createIndicator(onOpen);
    let active = null;
    let pendingCandidate = null;
    let candidateSince = 0;

    const unsubscribe = engine.onUpdate(() => {
        const playerPos = engine.player.position;
        const forward = new THREE.Vector3(Math.sin(engine.player.rotation.y), 0, Math.cos(engine.player.rotation.y));

        let best = null;
        let bestScore = -Infinity;

        for (const stand of stands) {
            // Un stand oculto por el filtro de `stand-search-panel.js`
            // (`stand.root.visible = false`) no debe seguir siendo
            // candidato al indicador "Ver vitrina" — si el booth ya no se
            // ve, tampoco debería poder "sentirse" caminando por ahí.
            if (stand.root && !stand.root.visible) {
                continue;
            }

            const toStand = stand.position.clone().sub(playerPos);
            toStand.y = 0;
            const distance = toStand.length();

            if (distance > radius || distance < 0.001) {
                continue;
            }

            // Prioriza el stand hacia el que mira el personaje (producto
            // punto del vector "adelante" contra la dirección hacia el
            // stand, de -1 a 1); desempata por cercanía dentro del radio.
            const facingScore = forward.dot(toStand.clone().normalize());
            const score = facingScore * 2 - distance / radius;

            if (score > bestScore) {
                bestScore = score;
                best = stand;
            }
        }

        if (best !== pendingCandidate) {
            pendingCandidate = best;
            candidateSince = performance.now();
        }

        // Espera `holdMs` de estabilidad antes de cambiar el stand activo
        // — evita que el indicador parpadee entre dos stands cercanos o
        // se abra y cierre repetidamente al pasar de largo.
        if (pendingCandidate !== active && performance.now() - candidateSince >= holdMs) {
            active = pendingCandidate;

            if (active) {
                indicator.show(active.business);
            } else {
                indicator.hide();
            }
        }
    });

    return () => {
        unsubscribe();
        indicator.destroy();
    };
}

let stylesInjected = false;

function injectStylesOnce() {
    if (stylesInjected) {
        return;
    }

    stylesInjected = true;

    const style = document.createElement('style');
    style.textContent = `
        .vpe-stand-indicator {
            position: fixed;
            left: 50%;
            bottom: 90px;
            z-index: 30;
            display: none;
            flex-direction: column;
            align-items: stretch;
            gap: 6px;
            padding: 10px 22px 12px;
            border-radius: 999px;
            background: #d7352a;
            color: #fff;
            font-family: inherit;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            opacity: 0;
            transform: translateX(-50%) translateY(8px);
            transition: opacity 160ms ease, transform 160ms ease;
        }

        .vpe-stand-indicator.is-visible {
            display: flex;
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .vpe-stand-indicator-label { text-align: center; }

        .vpe-stand-indicator-progress {
            height: 3px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.3);
            overflow: hidden;
        }

        .vpe-stand-indicator-progress-fill {
            display: block;
            height: 100%;
            width: 0%;
            background: #fff;
            border-radius: inherit;
        }

        @media (max-width: 640px) {
            .vpe-stand-indicator {
                bottom: 132px;
                font-size: 0.82rem;
                padding: 9px 18px 11px;
            }
        }
    `;
    document.head.appendChild(style);
}

function createIndicator(onOpen) {
    injectStylesOnce();

    const el = document.createElement('a');
    el.className = 'vpe-stand-indicator';

    const label = document.createElement('span');
    label.className = 'vpe-stand-indicator-label';

    const progress = document.createElement('span');
    progress.className = 'vpe-stand-indicator-progress';
    const progressFill = document.createElement('span');
    progressFill.className = 'vpe-stand-indicator-progress-fill';
    progress.appendChild(progressFill);

    el.appendChild(label);
    el.appendChild(progress);
    document.body.appendChild(el);

    let currentBusiness = null;
    let autoOpenTimer = null;

    // Cuenta regresiva visual de 3s (la barra) que abre la vitrina sola al
    // llegar a 100% — solo tiene sentido si hay un modal que abrir
    // (`onOpen`, IMM-033). Sin `onOpen` el indicador es un link normal sin
    // barra ni apertura automática.
    function cancelAutoOpen() {
        if (autoOpenTimer) {
            clearTimeout(autoOpenTimer);
            autoOpenTimer = null;
        }

        progressFill.style.transition = 'none';
        progressFill.style.width = '0%';
        // Fuerza a reflow para que la próxima animación arranque desde 0
        // en vez de reusar la transición ya cancelada.
        void progressFill.offsetWidth;
    }

    function startAutoOpen(business) {
        cancelAutoOpen();

        requestAnimationFrame(() => {
            progressFill.style.transition = `width ${AUTO_OPEN_MS}ms linear`;
            progressFill.style.width = '100%';
        });

        autoOpenTimer = setTimeout(() => {
            autoOpenTimer = null;
            onOpen?.(business);
        }, AUTO_OPEN_MS);
    }

    // El indicador sigue siendo un <a href> real — funciona sin JS y
    // permite abrir en pestaña nueva. Si se pasó `onOpen` (IMM-033), el
    // clic normal lo intercepta y abre el modal de inmediato (sin esperar
    // la barra) en vez de navegar; sin `onOpen`, navega directo.
    el.addEventListener('click', (event) => {
        if (onOpen && currentBusiness) {
            event.preventDefault();
            cancelAutoOpen();
            onOpen(currentBusiness);
        }
    });

    return {
        show(business) {
            currentBusiness = business;
            el.href = business.vitrina_url;
            label.textContent = business.name;
            progress.style.display = onOpen ? 'block' : 'none';
            el.classList.add('is-visible');

            if (onOpen) {
                startAutoOpen(business);
            }
        },
        hide() {
            el.classList.remove('is-visible');
            cancelAutoOpen();
            currentBusiness = null;
        },
        destroy() {
            cancelAutoOpen();
            el.remove();
        },
    };
}
