let messagingPromise;

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

        messagingSdk.onMessage(messaging, ({ notification, data }) => {
            if (Notification.permission !== 'granted' || !notification) return;

            const notice = new Notification(notification.title || 'Merkamigo', {
                body: notification.body,
                icon: '/icons/icon-192.png',
                data: { url: data?.url || '/' },
            });

            notice.onclick = () => window.location.assign(notice.data.url);
        });

        return { messaging, getToken: messagingSdk.getToken };
    })();

    return messagingPromise;
}

async function registerCurrentBrowser(requestPermission = false) {
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

    const serviceWorkerRegistration = await navigator.serviceWorker.ready;
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
        throw new Error('No fue posible registrar este navegador.');
    }

    return { status: 'granted' };
}

window.enableMerkamigoPush = () => registerCurrentBrowser(true);

window.addEventListener('load', () => {
    if (window.Notification?.permission === 'granted') {
        registerCurrentBrowser().catch(() => {});
    }
});
