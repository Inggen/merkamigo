/**
 * Panel técnico temporal para auditar los labs inmersivos (IMM-001).
 *
 * Mide, en vivo, exactamente lo que pide el TODO: FPS, draw calls,
 * triángulos, texturas/geometrías activas (proxy de memoria de GPU),
 * memoria JS (`performance.memory`, solo Chrome) y tiempo de carga
 * inicial (marca de navegación hasta el primer frame renderizado).
 *
 * Se activa con `?perf=1` en la URL (autoabre el panel, pensado para que
 * lo use el script de auditoría automatizada) o presionando la tecla
 * "P" en cualquier momento (para inspección manual). No se carga nada
 * de esto si nadie lo pide: el costo cuando está cerrado es un listener
 * de teclado y una lectura de `renderer.info` por frame, ya expuesta por
 * Three.js sin costo adicional.
 *
 * Expone `window.__immersivePerf` con la última muestra y el veredicto
 * de presupuesto, que es el gancho que usa
 * `scripts/audit-immersive-performance.mjs` para leer métricas desde
 * Chromium headless sin tener que parsear el DOM.
 */
import { evaluateBudget } from './immersive-perf-budget.js';

const LEVEL_COLORS = {
    ok: '#3ddc84',
    warning: '#f5b942',
    critical: '#ff5c5c',
    unknown: 'rgba(255,255,255,0.5)',
};

const LEVEL_LABELS = {
    ok: 'OK',
    warning: 'Atención',
    critical: 'Crítico',
    unknown: '—',
};

function formatBytes(bytes) {
    if (bytes === null || bytes === undefined) {
        return '—';
    }

    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

function detectDeviceProfile() {
    const params = new URLSearchParams(window.location.search);
    const forced = params.get('perfProfile');

    if (forced === 'mobile' || forced === 'desktop') {
        return forced;
    }

    const coarsePointer = window.matchMedia?.('(pointer: coarse)').matches;
    const narrow = window.innerWidth <= 900;

    return coarsePointer || narrow ? 'mobile' : 'desktop';
}

export function attachPerfMonitor({
    renderer,
    label = 'Experiencia inmersiva',
}) {
    const params = new URLSearchParams(window.location.search);
    const profile = detectDeviceProfile();

    const state = {
        frames: 0,
        lastFpsSampleAt: performance.now(),
        fps: 0,
        sceneReadyAt: null,
        loadMs: null,
        initialLoadBytes: estimateTransferredBytes(),
    };

    const root = document.createElement('div');
    root.setAttribute('data-immersive-perf-panel', '');
    root.style.cssText = `
        position: fixed;
        bottom: 16px;
        right: 16px;
        z-index: 9999;
        font-family: var(--font-mono, 'SFMono-Regular', ui-monospace, monospace);
        font-size: 11.5px;
        line-height: 1.5;
        color: #fff;
        background: rgba(8, 14, 24, 0.86);
        border: 1px solid rgba(255,255,255,0.14);
        border-radius: 12px;
        padding: 10px 12px;
        min-width: 220px;
        backdrop-filter: blur(8px);
        display: none;
        pointer-events: none;
        white-space: pre;
    `;
    document.body.appendChild(root);

    const setVisible = (visible) => {
        root.style.display = visible ? 'block' : 'none';
    };

    if (params.get('perf') === '1') {
        setVisible(true);
    }

    window.addEventListener('keydown', (event) => {
        if (event.key.toLowerCase() === 'p' && !event.metaKey && !event.ctrlKey && !event.altKey) {
            setVisible(root.style.display === 'none');
        }
    });

    function markSceneReady() {
        if (state.sceneReadyAt !== null) {
            return;
        }

        // `performance.now()` ya es relativo al inicio de la navegación, así
        // que su valor en el momento en que la escena queda lista es
        // exactamente el tiempo de carga que pide IMM-001.
        state.sceneReadyAt = performance.now();
        state.loadMs = state.sceneReadyAt;
    }

    function sample() {
        state.frames += 1;
        const now = performance.now();
        const elapsed = now - state.lastFpsSampleAt;

        if (elapsed >= 500) {
            state.fps = Math.round((state.frames * 1000) / elapsed);
            state.frames = 0;
            state.lastFpsSampleAt = now;
        }

        const info = renderer.info;
        const memory = performance.memory
            ? performance.memory.usedJSHeapSize
            : null;

        const snapshot = {
            label,
            profile,
            fps: state.fps,
            drawCalls: info.render.calls,
            triangles: info.render.triangles,
            textures: info.memory.textures,
            geometries: info.memory.geometries,
            jsHeapBytes: memory,
            initialLoadBytes: state.initialLoadBytes,
            loadMs: state.loadMs,
        };

        const budget = evaluateBudget(snapshot, profile);

        window.__immersivePerf = { ...snapshot, budget };

        if (root.style.display !== 'none') {
            render(snapshot, budget);
        }
    }

    function render(snapshot, budget) {
        const lines = [
            `${label} · panel técnico (P)`,
            `perfil: ${snapshot.profile}`,
            `FPS         ${String(snapshot.fps).padEnd(6)} [${LEVEL_LABELS[budget.metrics.fps]}]`,
            `Draw calls  ${String(snapshot.drawCalls).padEnd(6)} [${LEVEL_LABELS[budget.metrics.drawCalls]}]`,
            `Triángulos  ${String(snapshot.triangles).padEnd(6)} [${LEVEL_LABELS[budget.metrics.triangles]}]`,
            `Texturas    ${String(snapshot.textures).padEnd(6)} [${LEVEL_LABELS[budget.metrics.textures]}]`,
            `Geometrías  ${String(snapshot.geometries).padEnd(6)} [${LEVEL_LABELS[budget.metrics.geometries]}]`,
            `Carga inicial ${formatBytes(snapshot.initialLoadBytes)} [${LEVEL_LABELS[budget.metrics.initialLoadBytes]}]`,
            snapshot.jsHeapBytes !== null
                ? `Memoria JS  ${formatBytes(snapshot.jsHeapBytes)} [${LEVEL_LABELS[budget.metrics.jsHeapBytes]}]`
                : 'Memoria JS  no disponible (solo Chrome)',
            `Veredicto   ${LEVEL_LABELS[budget.overall]}`,
        ];

        root.textContent = lines.join('\n');
        root.style.borderColor = LEVEL_COLORS[budget.overall];
    }

    function estimateTransferredBytes() {
        try {
            return performance.getEntriesByType('resource')
                .concat(performance.getEntriesByType('navigation'))
                .reduce((total, entry) => total + (entry.transferSize || 0), 0);
        } catch {
            return null;
        }
    }

    return {
        sample,
        markSceneReady,
        setVisible,
    };
}
