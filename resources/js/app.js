import Hls from 'hls.js';
import './live-studio';
import './live-viewer';
import './push-notifications';
import './wompi-checkout';

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

        init() {
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
        },

        destroy() {
            window.clearTimeout(this.controlsTimer);
            document.removeEventListener('fullscreenchange', this.onFullscreenChange);
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
            }
        },

        changeVolume(event) {
            this.volume = Number(event.target.value);
            this.$refs.video.volume = this.volume;
            this.muted = this.volume === 0;
            this.$refs.video.muted = this.muted;
        },

        toggleFullscreen() {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                this.$root.requestFullscreen?.();
            }
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
