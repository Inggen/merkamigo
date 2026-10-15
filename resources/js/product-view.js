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
            this.$refs.lightboxVideo?.pause();
            this.$refs.backgroundVideo?.pause();
            this.activeMedia = this.gallery[index].url;
            this.activeAlt = this.gallery[index].alt;
            this.activeType = this.gallery[index].type;
            this.$nextTick(() => {
                if (!this.lightboxOpen && this.activeType === 'video'
                    && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
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
            this.$refs.lightboxVideo?.pause();
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
