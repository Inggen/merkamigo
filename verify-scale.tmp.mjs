import { chromium } from 'playwright-core';
import { execSync } from 'node:child_process';

const chromePath = execSync("ls -d ~/Library/Caches/ms-playwright/chromium-*/chrome-mac/Chromium.app/Contents/MacOS/Chromium 2>/dev/null | head -1").toString().trim();
const browser = await chromium.launch({ executablePath: chromePath, headless: true });
const context = await browser.newContext({ viewport: { width: 1400, height: 900 } });
const page = await context.newPage();

const errors = [];
page.on('pageerror', (err) => errors.push(err.message));

await page.goto('https://merkamigo.test/admin/login', { waitUntil: 'load' });
await page.fill('#admin-email', 'qa-superadmin-tmp3@example.com');
await page.fill('#admin-password', 'password123');
await page.click('button[type="submit"]');
await page.waitForLoadState('networkidle');

await page.goto('https://merkamigo.test/admin/immersive-plazas/1/editor-espacial', { waitUntil: 'load' });
await page.waitForFunction(() => {
    const el = document.querySelector('[id^="plaza-spatial-editor-"]');
    return el && el.dataset.cameraReady === 'true';
}, null, { timeout: 30000, polling: 300 });

const objectButtons = page.locator('[wire\\:click^="selectObject"]');
const count = await objectButtons.count();
let propIndex = -1, spawnIndex = -1;
for (let i = 0; i < count; i++) {
    const text = await objectButtons.nth(i).innerText();
    if (text.includes('Punto de aparición')) spawnIndex = i;
    else if (propIndex === -1) propIndex = i;
}

// Prop: el botón Escalar debe existir.
await objectButtons.nth(propIndex).click();
await page.waitForTimeout(400);
const scaleButtonForProp = await page.locator('#gizmo-mode-scale').count();
console.log('scale button visible for prop:', scaleButtonForProp > 0);

await page.click('#gizmo-mode-scale');
await page.waitForTimeout(300);
console.log('errors after clicking scale mode:', JSON.stringify(errors));

// Spawn: el botón Escalar NO debe existir, y el modo debe caer a Mover.
await objectButtons.nth(spawnIndex).click();
await page.waitForTimeout(400);
const scaleButtonForSpawn = await page.locator('#gizmo-mode-scale').count();
console.log('scale button visible for spawn (should be false):', scaleButtonForSpawn > 0);

const translateActive = await page.evaluate(() => document.getElementById('gizmo-mode-translate')?.classList.contains('is-active'));
console.log('translate button active after falling back from scale:', translateActive);

console.log('final errors:', JSON.stringify(errors));
await browser.close();
console.log('DONE');
