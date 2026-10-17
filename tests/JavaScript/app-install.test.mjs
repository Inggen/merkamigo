import test from 'node:test';
import assert from 'node:assert/strict';

globalThis.window = new EventTarget();
window.matchMedia = () => ({matches: false, addEventListener() {}});
const {appInstall} = await import('../../resources/js/app-install.js');
const controller = () => {
    const state = appInstall();
    state.opened = [];
    state.$flux = {modal: name => ({show: () => state.opened.push(name)})};
    return state;
};
const promptEvent = prompt => {
    const event = new Event('beforeinstallprompt', {cancelable: true});
    event.prompt = prompt;
    event.userChoice = Promise.resolve({outcome: 'dismissed'});
    window.dispatchEvent(event);
    return event;
};

test('installation unavailable leaves the action disabled without messages', async () => {
    const state = controller();
    await state.install();
    assert.deepEqual(state.opened, []);
    assert.equal(state.error, '');
    assert.equal(state.canInstall, false);
});
test('native install prompt is consumed once without fallback messages', async () => {
    let calls = 0;
    const event = promptEvent(async () => calls++);
    const state = controller();
    await state.install();
    assert.equal(calls, 1);
    assert.equal(event.defaultPrevented, true);
    assert.equal(state.installed, false);
    assert.equal(state.busy, false);
    await state.install();
    assert.deepEqual(state.opened, []);
    assert.equal(state.error, '');
    assert.equal(state.canInstall, false);
});
test('iPhone activation requests push permission directly, without an install detour', async () => {
    window.merkamigoPushNeedsInstall = () => true;
    window.enableMerkamigoPush = async () => ({status: 'granted'});
    const state = controller();
    assert.equal(state.canEnablePush, true);
    assert.equal(state.needsPushInstall, true);
    await state.enablePush();
    assert.deepEqual(state.opened, []);
    assert.equal(state.pushStatus, 'granted');
    window.merkamigoPushNeedsInstall = () => false;
});
test('a stale denied status does not prevent a fresh permission check', async () => {
    const state = controller();
    state.pushStatus = 'denied';
    let calls = 0;
    window.enableMerkamigoPush = async () => { calls++; return {status: 'granted'}; };
    await state.enablePush();
    assert.equal(calls, 1);
    assert.equal(state.pushStatus, 'granted');
    assert.deepEqual(state.opened, []);
});
test('push success hides the activation action and failure permits retry', async () => {
    const state = controller();
    window.enableMerkamigoPush = async () => {throw Error('Sin conexión');};
    await state.enablePush();
    assert.equal(state.error, 'Sin conexión');
    assert.equal(state.busy, false);
    window.enableMerkamigoPush = async () => ({status: 'granted'});
    await state.enablePush();
    assert.equal(state.pushStatus, 'granted');
    assert.equal(state.canEnablePush, false);
    assert.equal(state.error, '');
});
test('unavailable can be retried without an instructional modal', async () => {
    const state = controller();
    window.enableMerkamigoPush = async () => ({status: 'unavailable'});
    await state.enablePush();
    assert.equal(state.canEnablePush, true);
    assert.deepEqual(state.opened, []);
    window.enableMerkamigoPush = async () => ({status: 'granted'});
    await state.enablePush();
    assert.equal(state.canEnablePush, false);
});
test('successful installation updates the visible state', () => {
    const state = controller();
    window.dispatchEvent(new Event('appinstalled'));
    state.sync();
    assert.equal(state.installed, true);
});
