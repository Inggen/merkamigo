let messagingPromise;
let registrationPromise;

window.merkamigoPushNeedsInstall = () => {
    const iphone = /iPad|iPhone|iPod/.test(navigator.userAgent || '')
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    return iphone && !window.matchMedia('(display-mode: standalone)').matches && !navigator.standalone;
};

async function serviceWorker() {
    if (!('serviceWorker' in navigator)) throw new Error('Este navegador no admite notificaciones push.');
    await navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' });
    let timer;
    try {
        return await Promise.race([
            navigator.serviceWorker.ready,
            new Promise((_, reject) => {
                timer = setTimeout(() => reject(new Error('No se pudo activar el servicio de notificaciones. Recarga la página.')), 15000);
            }),
        ]);
    } finally {
        clearTimeout(timer);
    }
}

function publishStatus(status, error = '') {
    window.merkamigoPushStatus = { status, error };
    window.dispatchEvent(new CustomEvent('merkamigo-push-status', { detail: window.merkamigoPushStatus }));
}


async function messagingClient() {
    const [{ getApp, getApps, initializeApp }, messagingSdk] = await Promise.all([
        import('firebase/app'),
        import('firebase/messaging'),
    ]);

    if (!window.merkamigoPushConfig?.enabled || !await messagingSdk.isSupported()) {
        return null;
    }

    messagingPromise ??= (async () => {
        const app = getApps().length ? getApp() : initializeApp(window.merkamigoPushConfig.firebase);
        const messaging = messagingSdk.getMessaging(app);

        messagingSdk.onMessage(messaging, async ({ notification, data }) => {
            if (Notification.permission !== 'granted' || !notification) return;

            const registration = await serviceWorker();
            await registration.showNotification(notification.title || 'Merkamigo', {
                body: notification.body,
                icon: '/icons/icon-192.png',
                data: { url: data?.url || '/' },
            });
        });

        return { messaging, getToken: messagingSdk.getToken };
    })();

    return messagingPromise;
}

async function registerCurrentBrowser(requestPermission = false) {
    if (window.merkamigoPushNeedsInstall()) {
        return { status: 'install-required' };
    }

    if (!window.merkamigoPushConfig?.enabled || !('Notification' in window)) {
        return { status: 'unavailable' };
    }

    let permission = Notification.permission;

    if (requestPermission && permission === 'default') {
        permission = await Notification.requestPermission();
    }

    if (permission !== 'granted') {
        return { status: permission };
    }

    const client = await messagingClient();

    if (!client) {
        return { status: 'unavailable' };
    }

    const serviceWorkerRegistration = await serviceWorker();
    const token = await client.getToken(client.messaging, {
        vapidKey: window.merkamigoPushConfig.vapidKey,
        serviceWorkerRegistration,
    });

    if (!token) {
        return { status: 'unavailable' };
    }

    const response = await fetch(window.merkamigoPushConfig.registerUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({ platform: 'web', push_token: token }),
    });

    if (!response.ok) {
        throw new Error(response.status === 401 || response.status === 419
            ? 'Tu sesión venció. Ingresa nuevamente y activa las notificaciones.'
            : 'No fue posible registrar este dispositivo. Inténtalo nuevamente.');
    }

    return { status: 'granted' };
}

function register(requestPermission = false) {
    registrationPromise ??= registerCurrentBrowser(requestPermission)
        .then(result => {
            publishStatus(result.status);
            return result;
        })
        .catch(error => {
            publishStatus('error', error.message || 'No se pudieron activar las notificaciones.');
            throw error;
        })
        .finally(() => { registrationPromise = null; });
    return registrationPromise;
}

window.enableMerkamigoPush = () => register(true);

function restoreRegistration() {
    if (!window.merkamigoPushConfig?.enabled) return;
    if (window.Notification?.permission === 'granted') {
        register().catch(error => console.warn('Merkamigo push:', error.code || error.message));
    }
}

window.addEventListener('load', restoreRegistration);
document.addEventListener('livewire:navigated', restoreRegistration);
if (document.readyState === 'complete') restoreRegistration();
