import Hls from 'hls.js';
import './live-studio';
import './live-viewer';
import './push-notifications';
import './wompi-checkout';
import './qr-image-scanner';

// Registro del service worker (1.10 del TODO): habilita la instalación como
// PWA y la página offline informativa. Ver public/sw.js.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Si falla el registro (p. ej. en un navegador sin soporte
            // completo), la app sigue funcionando normalmente con red.
        });
    });
}

// Pedido del usuario (2026-10-11): en móvil, el header y la barra
// inferior nunca se ven los dos a la vez mientras se hace scroll — al
// bajar (explorando más contenido) se oculta la barra inferior y
// aparece el header; al subir (volviendo hacia arriba) se oculta el
// header y aparece la barra inferior. Cerca del tope de la página
// ambos vuelven a verse. Un solo store compartido evita que la barra,
// el header y el chatbot (que se reacomoda cuando la barra se oculta)
// terminen cada uno con su propia copia de esta lógica — eso fue
// justo lo que rompió el chatbot la primera vez que se tocó esto.
document.addEventListener('alpine:init', () => {
    window.Alpine.store('mobileChrome', {
        headerVisible: true,
        bottomNavVisible: true,
        lastScrollY: 0,
        update() {
            const currentY = Math.max(0, window.scrollY);

            if (currentY < 24) {
                this.headerVisible = true;
                this.bottomNavVisible = true;
            } else if (currentY > this.lastScrollY + 4) {
                // Scroll hacia abajo.
                this.bottomNavVisible = false;
                this.headerVisible = true;
            } else if (currentY < this.lastScrollY - 4) {
                // Scroll hacia arriba.
                this.headerVisible = false;
                this.bottomNavVisible = true;
            }

            this.lastScrollY = currentY;
        },
    });
});

window.addEventListener(
    'scroll',
    () => window.Alpine?.store('mobileChrome')?.update(),
    { passive: true },
);

window.merkamigoVideoPlayer = function (options = {}) {
    return {
        playing: false,
        muted: options.muted ?? true,
        volume: 1,
        currentTime: 0,
        duration: 0,
        fullscreen: false,
        controlsVisible: true,
        controlsTimer: null,
        hls: null,
        source: options.src ?? '',
        autoplayInView: options.autoplayInView ?? false,
        visibilityObserver: null,

        init() {
            // Pedido del usuario (2026-10-12): el volumen arranca al
            // 100% por defecto, y si lo baja en un video, los demás
            // reproductores del sitio deben recordar ese mismo nivel en
            // vez de volver a empezar en 100% cada vez — una preferencia
            // simple del visitante, en localStorage, no en la base de
            // datos (igual que el resto de preferencias de este tipo).
            try {
                const storedVolume = window.localStorage.getItem('merkamigo_video_volume');
                if (storedVolume !== null) {
                    this.volume = Math.min(1, Math.max(0, parseFloat(storedVolume)));
                }
            } catch {
                // localStorage inaccesible (modo privado) — se queda en
                // el 100% por defecto de arriba.
            }

            this.$refs.video.muted = this.muted;
            this.$refs.video.volume = this.volume;
            this.duration = this.$refs.video.duration || 0;

            if (this.source.includes('.m3u8') && !this.$refs.video.canPlayType('application/vnd.apple.mpegurl') && Hls.isSupported()) {
                this.hls = new Hls({
                    lowLatencyMode: true,
                    liveSyncDurationCount: 2,
                    liveMaxLatencyDurationCount: 5,
                });
                this.hls.loadSource(this.source);
                this.hls.attachMedia(this.$refs.video);
                this.hls.on(Hls.Events.ERROR, (_event, data) => {
                    if (!data.fatal) return;
                    if (data.type === Hls.ErrorTypes.NETWORK_ERROR) this.hls.startLoad();
                    else if (data.type === Hls.ErrorTypes.MEDIA_ERROR) this.hls.recoverMediaError();
                    else this.hls.destroy();
                });
            } else {
                this.$refs.video.src = this.source;
            }

            this.onFullscreenChange = () => {
                this.fullscreen = document.fullscreenElement === this.$root;
            };

            document.addEventListener('fullscreenchange', this.onFullscreenChange);

            // iOS Safari no dispara `fullscreenchange` para el modo de
            // pantalla completa nativo del <video> (`webkitEnterFullscreen`,
            // ver toggleFullscreen) — son eventos propios del elemento.
            this.onWebkitBeginFullscreen = () => { this.fullscreen = true; };
            this.onWebkitEndFullscreen = () => { this.fullscreen = false; };
            this.$refs.video.addEventListener('webkitbeginfullscreen', this.onWebkitBeginFullscreen);
            this.$refs.video.addEventListener('webkitendfullscreen', this.onWebkitEndFullscreen);

            // Videos del feed/reels: se reproducen solos (sin sonido, el
            // navegador no deja autoplay con audio sin gesto del usuario)
            // mientras estén visibles en pantalla, y se pausan en cuanto
            // el scroll los saca de vista — igual que Instagram/TikTok.
            // Cada reproductor observa su propio elemento, así que el
            // siguiente video arranca solo en cuanto entra a la vista sin
            // necesidad de coordinarlos entre sí.
            if (this.autoplayInView && 'IntersectionObserver' in window) {
                this.visibilityObserver = new IntersectionObserver(
                    (entries) => {
                        const entry = entries[entries.length - 1];
                        if (!entry) return;

                        if (entry.isIntersecting) {
                            this.$refs.video.play().catch(() => {});
                        } else {
                            this.$refs.video.pause();
                        }
                    },
                    { threshold: 0.5 },
                );
                this.visibilityObserver.observe(this.$root);
            }
        },

        destroy() {
            window.clearTimeout(this.controlsTimer);
            document.removeEventListener('fullscreenchange', this.onFullscreenChange);
            this.$refs.video.removeEventListener('webkitbeginfullscreen', this.onWebkitBeginFullscreen);
            this.$refs.video.removeEventListener('webkitendfullscreen', this.onWebkitEndFullscreen);
            this.visibilityObserver?.disconnect();
            this.hls?.destroy();
        },

        togglePlayback() {
            if (this.$refs.video.paused) {
                this.$refs.video.play().catch(() => {});
            } else {
                this.$refs.video.pause();
            }
        },

        seek(event) {
            this.$refs.video.currentTime = Number(event.target.value);
            this.currentTime = this.$refs.video.currentTime;
        },

        toggleMute() {
            this.muted = !this.muted;
            this.$refs.video.muted = this.muted;

            if (!this.muted && this.$refs.video.volume === 0) {
                this.$refs.video.volume = 1;
                this.volume = 1;
                this.persistVolume();
            }
        },

        persistVolume() {
            try {
                window.localStorage.setItem('merkamigo_video_volume', String(this.volume));
            } catch {
                // Igual que en init() — modo privado u otro bloqueo.
            }
        },

        changeVolume(event) {
            this.volume = Number(event.target.value);
            this.$refs.video.volume = this.volume;
            this.muted = this.volume === 0;
            this.$refs.video.muted = this.muted;
            this.persistVolume();
        },

        toggleFullscreen() {
            // Pedido del usuario (2026-10-12): el botón de pantalla
            // completa no hacía nada en iPhone. iOS Safari no soporta la
            // Fullscreen API estándar sobre un <div> que envuelve el
            // video — `$root.requestFullscreen?.()` ahí simplemente no
            // existe (u, en algunas versiones, existe pero la rechaza) y
            // el `?.()`/catch se tragaba el fallo en silencio. Ahí solo
            // el propio <video> puede entrar a pantalla completa, vía su
            // API nativa `webkitEnterFullscreen`.
            if (document.fullscreenElement) {
                document.exitFullscreen();
                return;
            }

            if (this.$root.requestFullscreen) {
                this.$root.requestFullscreen().catch(() => {
                    this.$refs.video.webkitEnterFullscreen?.();
                });
                return;
            }

            this.$refs.video.webkitEnterFullscreen?.();
        },

        showControls() {
            this.controlsVisible = true;
            window.clearTimeout(this.controlsTimer);

            if (this.playing) {
                this.controlsTimer = window.setTimeout(() => {
                    this.controlsVisible = false;
                }, 2600);
            }
        },

        formatTime(value) {
            if (!Number.isFinite(value)) return '0:00';

            const seconds = Math.floor(value % 60).toString().padStart(2, '0');
            const minutes = Math.floor(value / 60);

            return `${minutes}:${seconds}`;
        },
    };
};
