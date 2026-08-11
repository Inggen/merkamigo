/**
 * Preferencia de avatar del visitante (IMM-030): qué preset de
 * `avatarPresets` (`voxel-plaza-engine.js`) usar al construir el
 * personaje. Guardada solo en el navegador (`localStorage`), sin tabla
 * en base de datos — mismo patrón exacto que ya usa el motor para la
 * sensibilidad táctil (`vpe-touch-sensitivity`). Se lee tanto al entrar
 * a una plaza (antes de construir el `VoxelPlazaEngine`) como desde la
 * página de Ajustes > Avatar, para que ambas compartan una sola fuente
 * de verdad.
 */
const STORAGE_KEY = 'vpe-avatar';
const DEFAULT_AVATAR = 'hombre';

export function loadAvatarPreference() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        return stored === 'hombre' || stored === 'mujer' ? stored : DEFAULT_AVATAR;
    } catch {
        return DEFAULT_AVATAR;
    }
}

export function saveAvatarPreference(key) {
    try {
        localStorage.setItem(STORAGE_KEY, key);
    } catch {
        // Almacenamiento no disponible (ej. modo privado) — la
        // preferencia solo dura la sesión actual, sin romper nada.
    }
}
