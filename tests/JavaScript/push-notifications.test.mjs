import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
import test from 'node:test';

const source = readFileSync(new URL('../../resources/js/push-notifications.js', import.meta.url), 'utf8')
    .replace("import('firebase/app')", 'Promise.resolve(firebaseApp)')
    .replace("import('firebase/messaging')", 'Promise.resolve(firebaseMessaging)');

function browser({ readyState = 'loading', responseStatus = 201, iphone = false, standalone = false } = {}) {
    const events = new Map();
    const statuses = [];
    let registrations = 0;
    let savedTokens = 0;
    const window = {
        merkamigoPushConfig: { enabled: true, firebase: {}, vapidKey: 'public-key', registerUrl: '/api/v1/devices' },
        Notification: { permission: 'granted' },
        matchMedia: () => ({ matches: standalone }),
        addEventListener: (name, callback) => events.set(name, callback),
        dispatchEvent: event => statuses.push(event.detail),
    };
    const context = {
        window,
        Notification: window.Notification,
        navigator: { userAgent: iphone ? 'iPhone' : 'Chrome', serviceWorker: {
            register: async () => { registrations++; },
            ready: Promise.resolve({ showNotification: async () => {} }),
        } },
        document: {
            readyState,
            querySelector: () => ({ content: 'csrf' }),
            addEventListener: (name, callback) => events.set(name, callback),
        },
        firebaseApp: { getApps: () => [], initializeApp: () => ({}) },
        firebaseMessaging: {
            isSupported: async () => true,
            getMessaging: () => ({}),
            onMessage: () => {},
            getToken: async () => 'device-token',
        },
        fetch: async () => { savedTokens++; return { ok: responseStatus < 400, status: responseStatus }; },
        CustomEvent: class { constructor(name, options) { this.detail = options.detail; } },
        setTimeout, clearTimeout,
        console: { warn: () => {} },
    };
    vm.runInNewContext(source, context);
    return { window, events, statuses, registrations: () => registrations, savedTokens: () => savedTokens };
}

async function settle() {
    for (let i = 0; i < 40; i++) await Promise.resolve();
}

test('registers even when the module loads after the load event', async () => {
    const app = browser({ readyState: 'complete' });
    await settle();
    assert.equal(app.registrations(), 1);
    assert.equal(app.savedTokens(), 1);
    assert.equal(app.window.merkamigoPushStatus.status, 'granted');
});

test('registers after Livewire navigation and prevents concurrent requests', async () => {
    const app = browser();
    app.events.get('livewire:navigated')();
    app.events.get('load')();
    await settle();
    assert.equal(app.savedTokens(), 1);
    assert.equal(app.window.merkamigoPushStatus.status, 'granted');
});

test('permission alone does not mark a failed device registration as active', async () => {
    const app = browser({ responseStatus: 401 });
    await assert.rejects(app.window.enableMerkamigoPush(), /sesión venció/);
    assert.equal(app.window.merkamigoPushStatus.status, 'error');
    assert.match(app.window.merkamigoPushStatus.error, /sesión venció/);
});


test('iPhone requires installation before requesting a push token', async () => {
    const app = browser({ iphone: true });
    const result = await app.window.enableMerkamigoPush();
    assert.equal(result.status, 'install-required');
    assert.equal(app.savedTokens(), 0);
});

test('installed iPhone can register push', async () => {
    const app = browser({ iphone: true, standalone: true });
    const result = await app.window.enableMerkamigoPush();
    assert.equal(result.status, 'granted');
    assert.equal(app.savedTokens(), 1);
});
