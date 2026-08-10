#!/usr/bin/env node
/**
 * IMM-001 / IMM-002 — Auditoría automatizada de los labs inmersivos.
 *
 * Abre cada lab (Zipaquirá, Cajicá) en Chromium headless bajo distintos
 * perfiles de dispositivo (escritorio, Android medio, iPhone gama media)
 * y lee `window.__immersivePerf` -- expuesto por
 * `public/js/lib/immersive-perf-monitor.js` -- para capturar FPS, draw
 * calls, triángulos, texturas/geometrías activas y memoria JS. Los bytes
 * de carga inicial se miden aparte con el protocolo CDP
 * (`Network.loadingFinished`), porque el three.js servido desde esm.sh
 * no manda `Timing-Allow-Origin` y por lo tanto la Resource Timing API
 * del navegador reporta `transferSize: 0` para ese recurso cross-origin.
 *
 * Uso:
 *   node scripts/audit-immersive-performance.mjs
 *   node scripts/audit-immersive-performance.mjs --base-url=https://merkamigo.test
 *
 * Sale con código != 0 si algún perfil "mobile" queda en nivel "critical"
 * según public/js/lib/immersive-perf-budget.js -- ese es, por ahora (no
 * existe aún un panel de publicación/versionado, eso es Fase 1), el
 * mecanismo de bloqueo automatizable que pide IMM-002.
 */
import { chromium } from 'playwright-core';
import { existsSync } from 'node:fs';
import { evaluateBudget, PERFORMANCE_BUDGET } from '../public/js/lib/immersive-perf-budget.js';

const args = Object.fromEntries(process.argv.slice(2).map((arg) => {
    const [key, ...rest] = arg.replace(/^--/, '').split('=');
    return [key, rest.join('=') || true];
}));

const BASE_URL = args['base-url'] || process.env.IMMERSIVE_AUDIT_BASE_URL || 'https://merkamigo.test';
const SAMPLE_WINDOW_MS = Number(args['sample-ms'] || 6000);
const SAMPLE_INTERVAL_MS = 250;

const LABS = [
    { name: 'Zipaquirá', path: '/labs/zipa-inmersiva' },
    { name: 'Cajicá', path: '/labs/cajica-inmersiva' },
];

const DEVICE_PROFILES = [
    {
        name: 'Escritorio',
        profile: 'desktop',
        viewport: { width: 1440, height: 900 },
        deviceScaleFactor: 1,
        cpuThrottle: 1,
        network: null,
    },
    {
        name: 'Android medio',
        profile: 'mobile',
        viewport: { width: 393, height: 786 },
        deviceScaleFactor: 2.75,
        cpuThrottle: 4,
        // Aproxima un Android gama media en 4G real (no el "Fast 3G" de
        // DevTools, que es más lento que el terreno objetivo).
        network: { latencyMs: 60, downloadKbps: 6000, uploadKbps: 3000 },
    },
    {
        name: 'iPhone gama media',
        profile: 'mobile',
        viewport: { width: 390, height: 844 },
        deviceScaleFactor: 3,
        cpuThrottle: 2,
        network: { latencyMs: 40, downloadKbps: 10000, uploadKbps: 5000 },
    },
];

function resolveChromiumExecutable() {
    if (process.env.IMMERSIVE_AUDIT_CHROMIUM_PATH) {
        return process.env.IMMERSIVE_AUDIT_CHROMIUM_PATH;
    }

    const candidates = [
        `${process.env.HOME}/Library/Caches/ms-playwright/chromium-1234/chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing`,
    ];

    const found = candidates.find((candidate) => existsSync(candidate));

    if (found) {
        return found;
    }

    return null;
}

function formatBytes(bytes) {
    if (bytes === null || bytes === undefined) return '—';
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

function levelBadge(level) {
    return { ok: 'OK', warning: 'ATENCIÓN', critical: 'CRÍTICO', unknown: '—' }[level] ?? level;
}

const knownPayloadBytesByLab = new Map();

async function auditOne(browser, lab, device) {
    const context = await browser.newContext({
        viewport: device.viewport,
        deviceScaleFactor: device.deviceScaleFactor,
        userAgent: device.profile === 'mobile'
            ? 'Mozilla/5.0 (Linux; Android 13; Pixel 6a) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Mobile Safari/537.36'
            : undefined,
        isMobile: device.profile === 'mobile',
        hasTouch: device.profile === 'mobile',
    });

    const page = await context.newPage();
    const cdp = await context.newCDPSession(page);

    await cdp.send('Network.enable');

    let transferredBytes = 0;
    let loadingComplete = false;

    cdp.on('Network.loadingFinished', (event) => {
        if (!loadingComplete) {
            transferredBytes += event.encodedDataLength || 0;
        }
    });

    if (device.cpuThrottle > 1) {
        await cdp.send('Emulation.setCPUThrottlingRate', { rate: device.cpuThrottle });
    }

    if (device.network) {
        await cdp.send('Network.emulateNetworkConditions', {
            offline: false,
            latency: device.network.latencyMs,
            downloadThroughput: (device.network.downloadKbps * 1024) / 8,
            uploadThroughput: (device.network.uploadKbps * 1024) / 8,
        });
    }

    const url = `${BASE_URL}${lab.path}?perf=1&perfProfile=${device.profile}`;
    const startedAt = Date.now();
    const navTimeoutMs = Number(args['nav-timeout-ms'] || 45000);

    let samples = [];
    let loadWallClockMs = null;
    let timedOut = false;

    try {
        await page.goto(url, { waitUntil: 'load', timeout: navTimeoutMs });
        await page.waitForFunction('window.__immersivePerf && window.__immersivePerf.loadMs !== null', null, { timeout: navTimeoutMs });
        loadingComplete = true;
        loadWallClockMs = Date.now() - startedAt;

        const deadline = Date.now() + SAMPLE_WINDOW_MS;

        while (Date.now() < deadline) {
            // eslint-disable-next-line no-await-in-loop
            const snapshot = await page.evaluate(() => window.__immersivePerf).catch(() => null);
            if (snapshot) samples.push(snapshot);
            // eslint-disable-next-line no-await-in-loop
            await new Promise((resolve) => setTimeout(resolve, SAMPLE_INTERVAL_MS));
        }
    } catch (error) {
        timedOut = true;
        loadingComplete = true;
        loadWallClockMs = Date.now() - startedAt;
    }

    await context.close();

    if (timedOut) {
        const knownBytes = knownPayloadBytesByLab.get(lab.name);
        const estimatedMs = device.network && knownBytes
            ? Math.round(device.network.latencyMs + ((knownBytes * 8) / 1000) / device.network.downloadKbps * 1000)
            : null;

        return {
            lab: lab.name,
            device: device.name,
            profile: device.profile,
            timedOut: true,
            loadMs: null,
            loadWallClockMs,
            estimatedLoadMsAtThrottledSpeed: estimatedMs,
            transferredBytes: knownBytes ?? transferredBytes,
            avgFps: null,
            minFps: 0,
            drawCalls: null,
            triangles: null,
            textures: null,
            geometries: null,
            jsHeapBytes: null,
            budget: {
                overall: 'critical',
                metrics: { fps: 'critical', initialLoadBytes: 'critical', drawCalls: 'unknown', triangles: 'unknown', textures: 'unknown', geometries: 'unknown', jsHeapBytes: 'unknown' },
            },
        };
    }

    if (device.profile === 'desktop' || !knownPayloadBytesByLab.has(lab.name)) {
        knownPayloadBytesByLab.set(lab.name, transferredBytes);
    }

    const fpsValues = samples.map((s) => s.fps).filter((v) => v > 0);
    const last = samples.at(-1) ?? {};

    const result = {
        lab: lab.name,
        device: device.name,
        profile: device.profile,
        loadMs: last.loadMs ?? loadWallClockMs,
        loadWallClockMs,
        transferredBytes,
        avgFps: fpsValues.length ? Math.round(fpsValues.reduce((a, b) => a + b, 0) / fpsValues.length) : null,
        minFps: fpsValues.length ? Math.min(...fpsValues) : null,
        drawCalls: last.drawCalls ?? null,
        triangles: last.triangles ?? null,
        textures: last.textures ?? null,
        geometries: last.geometries ?? null,
        jsHeapBytes: last.jsHeapBytes ?? null,
    };

    result.budget = evaluateBudget({
        fps: result.minFps,
        initialLoadBytes: result.transferredBytes,
        drawCalls: result.drawCalls,
        triangles: result.triangles,
        textures: result.textures,
        geometries: result.geometries,
        jsHeapBytes: result.jsHeapBytes,
    }, device.profile);

    return result;
}

async function main() {
    const executablePath = resolveChromiumExecutable();

    if (!executablePath) {
        console.error(
            'No se encontró un Chromium instalado para Playwright.\n'
            + 'Instala uno con: npx playwright install chromium\n'
            + 'o define IMMERSIVE_AUDIT_CHROMIUM_PATH apuntando a un ejecutable existente.',
        );
        process.exit(1);
    }

    const browser = await chromium.launch({ executablePath, headless: true });
    const results = [];

    try {
        for (const lab of LABS) {
            for (const device of DEVICE_PROFILES) {
                // eslint-disable-next-line no-await-in-loop
                const result = await auditOne(browser, lab, device);
                results.push(result);

                if (result.timedOut) {
                    const estimate = result.estimatedLoadMsAtThrottledSpeed
                        ? ` (a esta velocidad, ~${Math.round(result.estimatedLoadMsAtThrottledSpeed / 1000)}s para transferir ${formatBytes(result.transferredBytes)})`
                        : '';
                    console.log(`✗ ${lab.name} · ${device.name} — no terminó de cargar en ${Math.round(result.loadWallClockMs / 1000)}s${estimate}`);
                } else {
                    console.log(`✓ ${lab.name} · ${device.name} — FPS min/avg: ${result.minFps}/${result.avgFps}, carga: ${formatBytes(result.transferredBytes)} en ${result.loadWallClockMs}ms, draw calls: ${result.drawCalls}, triángulos: ${result.triangles}`);
                }
            }
        }
    } finally {
        await browser.close();
    }

    console.log('\n=== Resumen de auditoría IMM-001 ===\n');
    console.table(results.map((r) => ({
        Lab: r.lab,
        Dispositivo: r.device,
        'FPS min': r.timedOut ? 'no cargó' : r.minFps,
        'FPS prom': r.timedOut ? '—' : r.avgFps,
        'Carga (ms)': r.timedOut ? `>${r.loadWallClockMs}` : r.loadWallClockMs,
        'Carga (bytes)': formatBytes(r.transferredBytes),
        'Draw calls': r.drawCalls ?? '—',
        Triángulos: r.triangles ?? '—',
        Texturas: r.textures ?? '—',
        Geometrías: r.geometries ?? '—',
        'Memoria JS': formatBytes(r.jsHeapBytes),
        Veredicto: levelBadge(r.budget.overall),
    })));

    const criticalMobileFailures = results.filter((r) => r.profile === 'mobile' && r.budget.overall === 'critical');

    if (criticalMobileFailures.length > 0) {
        console.error(`\n✗ ${criticalMobileFailures.length} perfil(es) móvil en nivel CRÍTICO respecto al presupuesto (IMM-002).`);
        criticalMobileFailures.forEach((r) => {
            const violated = Object.entries(r.budget.metrics).filter(([, level]) => level === 'critical').map(([metric]) => metric);
            console.error(`  - ${r.lab} / ${r.device}: ${violated.join(', ')}`);
        });
        process.exitCode = 1;
    } else {
        console.log('\n✓ Ningún perfil móvil está en nivel crítico según el presupuesto actual.');
    }

    console.log(`\nPresupuesto activo: ${JSON.stringify(PERFORMANCE_BUDGET, null, 2)}`);
}

main().catch((error) => {
    console.error(error);
    process.exit(1);
});
