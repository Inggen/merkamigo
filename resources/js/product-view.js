/** Shared gallery and checkout state for classic and elegant product themes. */
export function productView({ gallery = [], fallbackImage, productName, unitPrice }) {
    return {
        tab: 'descripcion',
        quantity: 1,
        unitPrice,
        shareSupported: typeof navigator !== 'undefined' && !!navigator.share,
        gallery,
        activeMedia: gallery[0]?.url ?? fallbackImage,
        activeAlt: gallery[0]?.alt ?? productName,
        activeType: gallery[0]?.type ?? 'image',
        lightboxOpen: false,
        lightboxIndex: 0,
        mediaPlaying: false,
        formatPrice(value) {
            return new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(value);
        },
        toggleBackgroundVideo() {
            const video = this.$refs.backgroundVideo;
            if (!video) return;
            if (video.paused) video.play().catch(() => {});
            else video.pause();
        },
        setActive(index) {
            if (!this.gallery[index]) return;
            // El lightbox usa el reproductor de marca (<x-media.video-player>),
            // que vive en su propio `x-data` — no se le puede pausar
            // directamente desde este scope, hay que buscar el <video>
            // nativo que trae adentro.
            this.$refs.lightboxVideo?.querySelector('video')?.pause();
            this.$refs.backgroundVideo?.pause();
            this.activeMedia = this.gallery[index].url;
            this.activeAlt = this.gallery[index].alt;
            this.activeType = this.gallery[index].type;
            this.$nextTick(() => {
                if (this.activeType !== 'video') return;

                if (this.lightboxOpen) {
                    // Pedido del usuario (2026-10-16): al ampliar un video de
                    // producto debe iniciar solo y a volumen completo — cruzar
                    // al `x-data` del reproductor vía un evento de DOM en vez
                    // de llamar a sus métodos directamente (ver `loadSource`
                    // en `merkamigoVideoPlayer`, app.js). Se dispara desde el
                    // propio <video> (con `bubbles: true`) para que suba hasta
                    // la raíz del reproductor, que es donde escucha
                    // `x-on:merkamigo-load-source` — `$refs.lightboxVideo` solo
                    // llega hasta el <div> envoltorio, no hasta esa raíz.
                    this.$refs.lightboxVideo?.querySelector('video')?.dispatchEvent(new CustomEvent('merkamigo-load-source', {
                        bubbles: true,
                        detail: { src: this.activeMedia, autoplay: true, muted: false },
                    }));
                } else if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    this.$refs.backgroundVideo?.play().catch(() => {});
                }
            });
        },
        openLightbox(index = 0) {
            this.setActive(index);
            this.lightboxIndex = index;
            this.lightboxOpen = true;
            document.body.classList.add('overflow-hidden');
        },
        closeLightbox() {
            this.$refs.lightboxVideo?.querySelector('video')?.pause();
            this.lightboxOpen = false;
            document.body.classList.remove('overflow-hidden');
        },
        showPrevious() {
            if (!this.gallery.length) return;
            this.lightboxIndex = (this.lightboxIndex - 1 + this.gallery.length) % this.gallery.length;
            this.setActive(this.lightboxIndex);
        },
        showNext() {
            if (!this.gallery.length) return;
            this.lightboxIndex = (this.lightboxIndex + 1) % this.gallery.length;
            this.setActive(this.lightboxIndex);
        },
        destroy() {
            if (this.lightboxOpen) document.body.classList.remove('overflow-hidden');
        },
    };
}

if (typeof window !== 'undefined') window.merkamigoProductView = productView;
