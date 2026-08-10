/**
 * Presupuesto de rendimiento para experiencias inmersivas (IMM-002).
 *
 * Estos números son el resultado de la auditoría IMM-001 sobre los labs
 * de Zipaquirá y Cajicá (ver docs/immersive-performance-audit.md) y de
 * las metas fijadas en el TODO: 30 FPS estables en móvil, menos de 4 MB
 * de carga inicial, límites de draw calls/texturas/memoria.
 *
 * No hay todavía un flujo de publicación/versionado (eso es Fase 1), así
 * que por ahora este presupuesto se aplica en dos sitios:
 *   1. El panel técnico temporal (`immersive-perf-monitor.js`) lo usa para
 *      pintar cada métrica en verde/ámbar/rojo mientras se navega.
 *   2. `scripts/audit-immersive-performance.mjs` lo usa para fallar (exit
 *      code != 0) cuando una escena excede un límite crítico, sirviendo
 *      de bloqueo automatizable hasta que exista un panel de publicación.
 */

export const PERFORMANCE_BUDGET = {
    fps: {
        // Objetivo de IMM-002: 30 FPS estables en móvil.
        targetMobile: 30,
        // Por debajo de esto se considera fallo crítico (no solo advertencia).
        criticalMobile: 20,
        targetDesktop: 55,
        criticalDesktop: 40,
    },
    initialLoadBytes: {
        warning: 3 * 1024 * 1024,
        critical: 4 * 1024 * 1024,
    },
    drawCalls: {
        warning: 150,
        critical: 220,
    },
    triangles: {
        warning: 250_000,
        critical: 400_000,
    },
    textures: {
        warning: 30,
        critical: 45,
    },
    geometries: {
        warning: 300,
        critical: 450,
    },
    jsHeapBytes: {
        warning: 200 * 1024 * 1024,
        critical: 350 * 1024 * 1024,
    },
};

/**
 * Evalúa una métrica puntual contra su presupuesto. `lowerIsBetter` se usa
 * para FPS, donde estar por DEBAJO del umbral es lo malo (para el resto de
 * métricas, estar por ENCIMA del umbral es lo malo).
 */
function evaluateMetric(value, { warning, critical }, lowerIsBetter = false) {
    if (value === null || value === undefined || Number.isNaN(value)) {
        return 'unknown';
    }

    if (lowerIsBetter) {
        if (value <= critical) return 'critical';
        if (value <= warning) return 'warning';
        return 'ok';
    }

    if (value >= critical) return 'critical';
    if (value >= warning) return 'warning';
    return 'ok';
}

/**
 * Evalúa una muestra completa del monitor de rendimiento contra el
 * presupuesto. `profile` es 'mobile' o 'desktop' y decide qué objetivo de
 * FPS aplica.
 */
export function evaluateBudget(sample, profile = 'desktop') {
    const fpsBudget = profile === 'mobile'
        ? { warning: PERFORMANCE_BUDGET.fps.targetMobile, critical: PERFORMANCE_BUDGET.fps.criticalMobile }
        : { warning: PERFORMANCE_BUDGET.fps.targetDesktop, critical: PERFORMANCE_BUDGET.fps.criticalDesktop };

    const results = {
        fps: evaluateMetric(sample.fps, fpsBudget, true),
        initialLoadBytes: evaluateMetric(sample.initialLoadBytes, PERFORMANCE_BUDGET.initialLoadBytes),
        drawCalls: evaluateMetric(sample.drawCalls, PERFORMANCE_BUDGET.drawCalls),
        triangles: evaluateMetric(sample.triangles, PERFORMANCE_BUDGET.triangles),
        textures: evaluateMetric(sample.textures, PERFORMANCE_BUDGET.textures),
        geometries: evaluateMetric(sample.geometries, PERFORMANCE_BUDGET.geometries),
        jsHeapBytes: evaluateMetric(sample.jsHeapBytes, PERFORMANCE_BUDGET.jsHeapBytes),
    };

    const levels = Object.values(results);
    const overall = levels.includes('critical') ? 'critical' : levels.includes('warning') ? 'warning' : 'ok';

    return { overall, metrics: results };
}
