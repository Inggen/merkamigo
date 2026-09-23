// Service worker mínimo (1.10 del TODO): permite instalar Merkamigo como
// PWA y muestra una página informativa cuando no hay conexión. No cachea
// contenido dinámico (vitrinas, plaza, panel) ni promete operación offline
// completa — cada visita sigue necesitando red para ver datos reales.
const CACHE_NAME = 'merkamigo-shell-v1';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.add(OFFLINE_URL)),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)),
        )),
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') {
        return;
    }

    event.respondWith(
        fetch(event.request).catch(() => caches.match(OFFLINE_URL)),
    );
});

self.addEventListener('push', (event) => {
    const payload = event.data?.json() || {};
    const notification = payload.notification || payload.data?.notification || {};
    const data = payload.data || {};

    event.waitUntil(self.registration.showNotification(notification.title || 'Merkamigo', {
        body: notification.body || '',
        icon: notification.icon || '/icons/icon-192.png',
        badge: notification.badge || '/icons/icon-192.png',
        data: { url: data.url || notification.click_action || '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = new URL(event.notification.data?.url || '/', self.location.origin).href;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
            const existing = windows.find((client) => client.url === url);

            return existing ? existing.focus() : self.clients.openWindow(url);
        }),
    );
});
