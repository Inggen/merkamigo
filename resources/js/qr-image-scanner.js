import { BrowserQRCodeReader } from '@zxing/browser';

const MAX_IMAGE_BYTES = 15 * 1024 * 1024;

window.merkamigoQrImageScanner = function (livewire, messages = {}, options = {}) {
    return {
        reading: false,
        error: '',
        cameraOpen: false,
        controls: null,
        cameraSession: 0,

        async submitCode(code) {
            await livewire.set(options.property ?? 'scanInput', code, false);
            await livewire.call(options.action ?? 'scan');
        },

        async startCamera() {
            if (this.cameraOpen || this.reading) return;

            this.error = '';
            this.cameraOpen = true;
            const session = ++this.cameraSession;
            await this.$nextTick();

            try {
                const controls = await new BrowserQRCodeReader().decodeFromConstraints(
                    { video: { facingMode: { ideal: 'environment' } }, audio: false },
                    this.$refs.qrVideo,
                    async (result, error, controls) => {
                        if (!result || this.reading || session !== this.cameraSession) return;

                        this.reading = true;
                        controls.stop();
                        this.stopCamera();

                        try {
                            await this.submitCode(result.getText().trim());
                        } catch {
                            this.error = messages.unknownError;
                        } finally {
                            this.reading = false;
                        }
                    },
                );

                if (session !== this.cameraSession) controls.stop();
                else this.controls = controls;
            } catch {
                if (session !== this.cameraSession) return;
                this.stopCamera();
                this.error = messages.cameraError;
            }
        },

        stopCamera() {
            ++this.cameraSession;
            this.controls?.stop();
            this.controls = null;
            this.cameraOpen = false;
        },

        destroy() {
            this.stopCamera();
        },

        async decode(event) {
            const input = event.currentTarget;
            const file = input.files?.[0];

            this.error = '';

            if (!file || !file.type.startsWith('image/')) {
                this.error = messages.invalidImage;
                input.value = '';

                return;
            }

            if (file.size > MAX_IMAGE_BYTES) {
                this.error = messages.imageTooLarge;
                input.value = '';

                return;
            }

            this.reading = true;
            const imageUrl = URL.createObjectURL(file);

            try {
                const result = await new BrowserQRCodeReader().decodeFromImageUrl(imageUrl);
                const code = result.getText().trim();

                if (!code) {
                    throw new Error('empty-qr');
                }

                await this.submitCode(code);
            } catch (error) {
                const notFound = error?.name === 'NotFoundException'
                    || error?.constructor?.name === 'NotFoundException';

                this.error = notFound ? messages.qrNotFound : messages.unknownError;
            } finally {
                URL.revokeObjectURL(imageUrl);
                input.value = '';
                this.reading = false;
            }
        },
    };
};
