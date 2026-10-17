let installPrompt;
let installed = window.matchMedia('(display-mode: standalone)').matches || Boolean(navigator.standalone);
const publish = () => window.dispatchEvent(new CustomEvent('merkamigo-install-status'));

window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    installPrompt = event;
    publish();
});
window.addEventListener('appinstalled', () => {
    installed = true;
    installPrompt = null;
    publish();
});
window.matchMedia('(display-mode: standalone)').addEventListener('change', event => {
    installed = event.matches || Boolean(navigator.standalone);
    publish();
});

export function appInstall() {
    return {
        installed,
        canInstall: Boolean(installPrompt),
        headerHeight: 0,
        headerObserver: null,
        init() {
            const header = document.querySelector('[data-app-header]');
            if (!header) return;
            this.headerObserver = new ResizeObserver(() => {
                this.headerHeight = header.getBoundingClientRect().height;
            });
            this.headerObserver.observe(header);
        },
        destroy() { this.headerObserver?.disconnect(); },
        busy: false,
        pushStatus: window.merkamigoPushStatus?.status ?? (window.Notification?.permission === 'denied' ? 'denied' : 'default'),
        error: '',
        get canEnablePush() {
            return this.pushStatus !== 'granted';
        },
        get needsPushInstall() { return Boolean(window.merkamigoPushNeedsInstall?.()); },
        sync() { this.installed = installed; this.canInstall = Boolean(installPrompt); },
        async install() {
            if (!installPrompt || this.busy) return;
            const prompt = installPrompt;
            installPrompt = null;
            this.canInstall = false;
            this.busy = true;
            this.error = '';
            try {
                await prompt.prompt();
                await prompt.userChoice;
            } catch {
                this.error = 'No se pudo completar la instalación.';
            } finally {
                this.busy = false;
            }
        },
        async enablePush() {
            if (!this.canEnablePush || this.busy) return;
            this.busy = true;
            this.error = '';
            try {
                this.pushStatus = (await window.enableMerkamigoPush()).status;

            } catch (error) {
                this.error = error.message || 'No se pudieron activar las notificaciones.';
            } finally {
                this.busy = false;
            }
        },
    };
}
window.merkamigoAppInstall = appInstall;
