import { BrowserQRCodeReader } from '@zxing/browser';

const MAX_IMAGE_BYTES = 15 * 1024 * 1024;

window.merkamigoQrImageScanner = function (livewire, messages = {}) {
    return {
        reading: false,
        error: '',

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

                await livewire.set('scanInput', code, false);
                await livewire.call('scan');
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
