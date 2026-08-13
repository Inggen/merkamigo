/**
 * IMM-043: envía telemetría de navegación de la plaza inmersiva al
 * endpoint público `POST /api/v1/inmersivo/plazas/{plaza}/eventos`
 * (`RegisterImmersiveEvent`, deduplicación/filtro de bots del lado del
 * servidor). Mismo idioma que el resto del motor: se construye una vez
 * por escena y se pasa como callback inyectado (`track`) a
 * `attachStandProximity`/`createVitrinaModal`/`attachSearchPanel`, igual
 * que ya se hace con `onOpen`/`vitrinaModal` — sin introducir un event-bus
 * nuevo. Nunca debe romper la experiencia: cualquier error de red/JSON se
 * ignora en silencio (mismo criterio que `dynamic-stand-loader.js`).
 */
/**
 * Muestra única de rendimiento por sesión (IMM-043: "rendimiento por
 * dispositivo"), reutilizando el snapshot que `immersive-perf-monitor.js`
 * ya calcula cada frame en `window.__immersivePerf` — no se duplica esa
 * lógica, solo se lee una vez, con tiempo suficiente para que el FPS se
 * estabilice tras la carga inicial.
 */
export function schedulePerformanceSample(track, delayMs = 6000) {
    setTimeout(() => {
        const snapshot = window.__immersivePerf;

        if (! snapshot) {
            return;
        }

        track('performance_sample', {
            metadata: {
                profile: String(snapshot.profile ?? ''),
                fps: String(snapshot.fps ?? ''),
                verdict: String(snapshot.budget?.overall ?? ''),
            },
        });
    }, delayMs);
}

export function createTracker(plazaId) {
    return function track(type, { businessId = null, productId = null, metadata = null } = {}) {
        if (! plazaId) {
            return;
        }

        try {
            fetch(`/api/v1/inmersivo/plazas/${plazaId}/eventos`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                keepalive: true,
                body: JSON.stringify({
                    type,
                    business_id: businessId,
                    product_id: productId,
                    metadata,
                }),
            }).catch(() => {});
        } catch {
            // Sin `fetch`/almacenamiento disponible — la telemetría se
            // pierde, pero la experiencia sigue funcionando igual.
        }
    };
}
