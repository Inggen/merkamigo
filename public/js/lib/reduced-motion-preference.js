/**
 * Preferencia de movimiento reducido del visitante (IMM-042): apaga las
 * animaciones puramente decorativas del motor (balanceo idle de NPCs,
 * nunca `updatePlayer`/`updateCamera`/`updatePlayerAnimation`). Guardada
 * solo en el navegador (`localStorage`), mismo patrón exacto que
 * `avatar-preference.js`. El valor por defecto respeta la preferencia del
 * sistema operativo (`prefers-reduced-motion`) hasta que el visitante la
 * cambie explícitamente.
 */
const STORAGE_KEY = 'vpe-reduced-motion';

function systemPrefersReducedMotion() {
    try {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch {
        return false;
    }
}

export function loadReducedMotionPreference() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        if (stored === 'true') {
            return true;
        }

        if (stored === 'false') {
            return false;
        }

        return systemPrefersReducedMotion();
    } catch {
        return systemPrefersReducedMotion();
    }
}

export function saveReducedMotionPreference(enabled) {
    try {
        localStorage.setItem(STORAGE_KEY, enabled ? 'true' : 'false');
    } catch {
        // Almacenamiento no disponible (ej. modo privado) — la
        // preferencia solo dura la sesión actual, sin romper nada.
    }
}
