/**
 * Preloader de entrada a la experiencia inmersiva: pedido explícito del
 * usuario — mientras carga la escena (Three.js desde CDN, GLB/texturas,
 * stands y props dinámicos vía `dynamic-stand-loader.js`), se muestra un
 * overlay de pantalla completa con textos aleatorios rotativos ("Armando
 * la iglesia...", "Generando vegetación..."), en vez de dejar al
 * visitante mirando un lienzo vacío o a medio construir.
 *
 * El overlay ya vive como HTML estático en `generic-plaza.blade.php`
 * (visible desde el primer pintado, sin esperar a que este módulo ni
 * Three.js terminen de cargar) — este módulo solo le agrega la rotación
 * de textos y expone `hide()` para cuando la escena esté lista
 * (`engine.perf.markSceneReady()`, mismo punto de cierre que ya usa cada
 * escena).
 */
const MESSAGES = [
    'Armando la iglesia...',
    'Generando vegetación...',
    'Colocando los stands...',
    'Instalando las fuentes...',
    'Puliendo los andenes...',
    'Sembrando árboles...',
    'Preparando tu personaje...',
    'Cargando las vitrinas...',
    'Iluminando la plaza...',
    'Acomodando las bancas...',
    'Últimos detalles...',
];

// Salvavidas: si algo se traba antes de que la escena avise que está
// lista (ej. la CDN de Three.js tarda o falla), el overlay no debe
// quedarse ahí para siempre — se oculta solo tras este tiempo.
const SAFETY_TIMEOUT_MS = 30000;

function shuffled(list) {
    const copy = [...list];

    for (let i = copy.length - 1; i > 0; i -= 1) {
        const j = Math.floor(Math.random() * (i + 1));
        [copy[i], copy[j]] = [copy[j], copy[i]];
    }

    return copy;
}

export function createPreloader({
    containerId = 'voxel-preloader',
    textId = 'voxel-preloader-text',
    intervalMs = 1800,
} = {}) {
    const container = document.getElementById(containerId);
    const textEl = document.getElementById(textId);

    if (!container || !textEl) {
        return { hide() {} };
    }

    const messages = shuffled(MESSAGES);
    let index = 0;
    textEl.textContent = messages[index];

    const rotation = window.setInterval(() => {
        index = (index + 1) % messages.length;
        textEl.textContent = messages[index];
    }, intervalMs);

    const safety = window.setTimeout(() => hide(), SAFETY_TIMEOUT_MS);

    let hidden = false;

    function hide() {
        if (hidden) {
            return;
        }

        hidden = true;
        window.clearInterval(rotation);
        window.clearTimeout(safety);
        container.classList.add('is-hidden');
        window.setTimeout(() => container.remove(), 500);
    }

    return { hide };
}
