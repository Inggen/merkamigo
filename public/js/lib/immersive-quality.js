/**
 * IMM-040: calidad adaptativa y modo ligero. Resuelve un nivel
 * (`ligero`/`equilibrado`/`alto`) a partir de: 1) override manual del
 * visitante (localStorage, mismo patrón que `avatar-preference.js`), 2)
 * clasificación de dispositivo — reutiliza `detectDeviceProfile()` de
 * `immersive-perf-monitor.js`, no se duplica el heurístico — para elegir
 * entre los valores `mobile_quality_profile`/`desktop_quality_profile` ya
 * configurados por plaza en el admin, 3) alto/ligero fijo como último
 * respaldo, igual que el default de esas dos columnas.
 */
import { detectDeviceProfile } from './immersive-perf-monitor.js';

const STORAGE_KEY = 'vpe-quality-override';

export const QUALITY_TIERS = ['ligero', 'equilibrado', 'alto'];

export const QUALITY_PRESETS = {
    ligero: { pixelRatioCap: 1, shadows: false, shadowMapSize: 1024, fogFar: 140 },
    equilibrado: { pixelRatioCap: 1.5, shadows: true, shadowMapSize: 1024, fogFar: 200 },
    alto: { pixelRatioCap: 2, shadows: true, shadowMapSize: 2048, fogFar: 260 },
};

export function loadQualityOverride() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        return QUALITY_TIERS.includes(stored) ? stored : null;
    } catch {
        return null;
    }
}

export function saveQualityOverride(tier) {
    try {
        if (tier === null) {
            localStorage.removeItem(STORAGE_KEY);
        } else {
            localStorage.setItem(STORAGE_KEY, tier);
        }
    } catch {
        // Sin almacenamiento disponible — el override solo dura la sesión
        // actual, sin romper nada.
    }
}

/**
 * @returns {'ligero'|'equilibrado'|'alto'}
 */
export function resolveQualityTier({ mobileProfile = 'ligero', desktopProfile = 'alto' } = {}) {
    const override = loadQualityOverride();

    if (override) {
        return override;
    }

    return detectDeviceProfile() === 'mobile' ? mobileProfile : desktopProfile;
}

export function getQualitySettings(tier) {
    return QUALITY_PRESETS[tier] ?? QUALITY_PRESETS.alto;
}

/**
 * Degradación automática (criterio de aceptación de IMM-040: "la
 * experiencia entra automáticamente en modo ligero en dispositivos de
 * baja capacidad"). Observa `window.__immersivePerf.budget` — ya
 * calculado cada frame por `immersive-perf-monitor.js`, no se duplica esa
 * lógica — y baja un nivel si el veredicto es crítico durante
 * `consecutiveSamplesRequired` muestras seguidas y no hay override manual
 * (un override manual siempre gana, nunca se pisa en silencio). Notifica
 * una sola vez vía `onDowngrade`, quien decide cómo avisarlo (recargar la
 * escena con la calidad nueva, mostrar un aviso, etc.) — este módulo no
 * toca el DOM.
 */
export function watchForAutomaticDowngrade({ currentTier, onDowngrade, consecutiveSamplesRequired = 6, intervalMs = 1000 }) {
    let criticalStreak = 0;
    let downgraded = false;

    const timer = setInterval(() => {
        if (downgraded || loadQualityOverride()) {
            return;
        }

        const verdict = window.__immersivePerf?.budget?.overall;

        if (verdict === 'critical') {
            criticalStreak += 1;
        } else {
            criticalStreak = 0;
        }

        if (criticalStreak < consecutiveSamplesRequired) {
            return;
        }

        const currentIndex = QUALITY_TIERS.indexOf(currentTier);

        if (currentIndex <= 0) {
            return;
        }

        downgraded = true;
        clearInterval(timer);
        onDowngrade(QUALITY_TIERS[currentIndex - 1]);
    }, intervalMs);

    return () => clearInterval(timer);
}
