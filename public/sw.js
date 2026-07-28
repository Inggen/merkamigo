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
