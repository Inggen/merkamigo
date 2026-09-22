const CAMERA_EFFECTS = {
    none: { filter: 'none' },
    soft: { filter: 'blur(1.4px) saturate(1.1) brightness(1.05)' },
    mono: { filter: 'grayscale(1) contrast(1.08)' },
    sepia: { filter: 'sepia(0.75) contrast(1.05)' },
};

window.merkamigoLiveStudio = function (options) {
    return {
        state: 'idle',
        message: '',
        stream: null,
        peer: null,
        session: null,
        cameras: [],
        microphones: [],
        cameraId: '',
        microphoneId: '',
        cameraEnabled: true,
        microphoneEnabled: true,
        cameraMirrored: true,
        cameraEffect: 'none',
        cameraStream: null,
        canvasStream: null,
        effectsFrame: null,
        effectsReady: false,
        effectsSupported: false,
        floatingReactions: [],
        sellerReactionsOpen: false,
        overlayProduct: null,
        overlayProductImage: null,
        overlayPoll: null,
        introVideoActive: false,
        introStream: null,
        reconnectTimer: null,
        reconnectAttempts: 0,
        stopping: false,

        async init() {
            this.effectsSupported =
                typeof HTMLCanvasElement !== 'undefined' &&
                typeof HTMLCanvasElement.prototype.captureStream === 'function';

            this.setProductOverlay(options.product ?? null);
            this.setPollOverlay(options.poll ?? null);
            this.introVideoActive = options.introVideoActive ?? false;

            this.$nextTick(() => {
                if (this.introVideoActive) this.setIntroVideoActive(true);
                if (options.isLive) this.startPreview().then(() => this.startBroadcast());
            });

            if (!navigator.mediaDevices?.getUserMedia || !window.RTCPeerConnection) {
                this.state = 'unsupported';
                this.message = 'Este navegador no permite transmitir desde la cámara.';
            }
        },

        async startPreview() {
            this.message = '';

            try {
                this.stopTracks();
                this.cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: this.cameraId
                        ? { deviceId: { exact: this.cameraId }, width: { ideal: 1280 }, height: { ideal: 720 }, frameRate: { ideal: 30 } }
                        : { width: { ideal: 1280 }, height: { ideal: 720 }, frameRate: { ideal: 30 }, facingMode: 'user' },
                    audio: this.microphoneId
                        ? { deviceId: { exact: this.microphoneId }, echoCancellation: true, noiseSuppression: true }
                        : { echoCancellation: true, noiseSuppression: true },
                });
                this.$refs.cameraSource.srcObject = this.cameraStream;
                this.state = 'preview';
                await this.loadDevices();

                if (this.introVideoActive) await this.setIntroVideoActive(true);
                await this.startCameraPipeline();
            } catch (error) {
                this.state = 'error';
                this.message = error.name === 'NotAllowedError'
                    ? 'Permite el acceso a la cámara y al micrófono para continuar.'
                    : 'No fue posible abrir la cámara o el micrófono.';
            }
        },

        async loadDevices() {
            const devices = await navigator.mediaDevices.enumerateDevices();
            this.cameras = devices.filter((device) => device.kind === 'videoinput');
            this.microphones = devices.filter((device) => device.kind === 'audioinput');
            this.cameraId ||= this.cameraStream?.getVideoTracks()[0]?.getSettings().deviceId || '';
            this.microphoneId ||= this.cameraStream?.getAudioTracks()[0]?.getSettings().deviceId || '';
        },

        async changeDevices() {
            if (this.state !== 'live' && this.state !== 'connecting') {
                await this.startPreview();
            }
        },

        applyCameraEffect(effect) {
            if (!(effect in CAMERA_EFFECTS)) return;
            this.cameraEffect = effect;

            if (!this.cameraStream) return;

            if (!this.effectsReady) this.startCameraPipeline();
        },

        cameraFilter() {
            return CAMERA_EFFECTS[this.cameraEffect]?.filter || 'none';
        },

        isBroadcasting() {
            return this.peer && ['connected', 'connecting', 'new'].includes(this.peer.connectionState);
        },

        replaceVideoTrack(track) {
            if (! track) return;

            this.peer?.getSenders().forEach((sender) => {
                if (sender.track?.kind === 'video') sender.replaceTrack(track);
            });
        },

        startCameraPipeline() {
            return new Promise((resolve) => {
                const source = this.$refs.cameraSource;

                if (!this.effectsSupported || !source) {
                    this.stream = this.cameraStream;
                    this.$refs.preview.srcObject = this.stream;
                    resolve();
                    return;
                }

                const onSourceReady = () => {
                    if (!this.startEffectsPipeline()) {
                        this.stream = this.cameraStream;
                        this.$refs.preview.srcObject = this.stream;
                    }
                    resolve();
                };

                if (source.readyState >= 1 && source.videoWidth) {
                    onSourceReady();
                    return;
                }

                source.addEventListener('loadedmetadata', onSourceReady, { once: true });
            });
        },

        startEffectsPipeline() {
            if (this.effectsReady) return true;

            const source = this.$refs.cameraSource;
            const canvas = this.$refs.cameraCanvas;
            if (!source || !canvas || !source.videoWidth) return false;

            canvas.width = 1280;
            canvas.height = 720;

            const context = canvas.getContext('2d');
            const renderFrame = () => {
                if (!this.effectsReady) return;

                const intro = this.$refs.introSource;
                if (this.introVideoActive && intro?.readyState >= 2 && intro.videoWidth) {
                    context.filter = 'none';
                    this.drawMediaFrame(context, intro, true);
                } else if (!this.cameraEnabled) {
                    context.filter = 'none';
                    context.fillStyle = '#000';
                    context.fillRect(0, 0, canvas.width, canvas.height);
                } else {
                    context.filter = CAMERA_EFFECTS[this.cameraEffect]?.filter || 'none';
                    this.drawCameraFrame(context, source);
                }

                context.filter = 'none';
                this.drawBroadcastOverlays(context);

                this.effectsFrame = window.requestAnimationFrame(renderFrame);
            };

            this.effectsReady = true;
            renderFrame();

            this.canvasStream = canvas.captureStream(30);
            this.stream = new MediaStream([
                ...this.canvasStream.getVideoTracks(),
                ...(this.currentAudioTrack() ? [this.currentAudioTrack()] : []),
            ]);
            // La vista del estudio conserva sus controles HTML; la pista que
            // sale por WHIP usa el canvas con productos y reacciones incluidos.
            this.$refs.preview.srcObject = this.cameraStream;

            if (this.isBroadcasting()) this.replaceVideoTrack(this.canvasStream.getVideoTracks()[0]);

            return true;
        },

        drawCameraFrame(context, source) {
            this.drawMediaFrame(context, source, false, this.cameraMirrored);
        },

        drawMediaFrame(context, source, cover = false, mirrored = false) {
            const canvas = this.$refs.cameraCanvas;
            const scale = cover
                ? Math.max(canvas.width / source.videoWidth, canvas.height / source.videoHeight)
                : Math.min(canvas.width / source.videoWidth, canvas.height / source.videoHeight);
            const drawWidth = source.videoWidth * scale;
            const drawHeight = source.videoHeight * scale;

            context.save();
            if (mirrored) {
                context.translate(canvas.width, 0);
                context.scale(-1, 1);
            }

            context.drawImage(
                source,
                (canvas.width - drawWidth) / 2,
                (canvas.height - drawHeight) / 2,
                drawWidth,
                drawHeight,
            );
            context.restore();
        },

        async setIntroVideoActive(active) {
            this.introVideoActive = Boolean(active);
            const source = this.$refs.introSource;

            if (!source) return;

            if (this.introVideoActive) {
                source.currentTime = 0;
                await source.play().catch(() => {});
                this.captureIntroStream(source);
            } else {
                source.pause();
            }

            this.syncBroadcastAudio();
        },

        captureIntroStream(source) {
            if (this.introStream) return;

            const capture = source.captureStream || source.mozCaptureStream;
            if (typeof capture === 'function') {
                this.introStream = capture.call(source);
            }
        },

        currentAudioTrack() {
            if (this.introVideoActive) {
                const introTrack = this.introStream?.getAudioTracks()[0];
                if (introTrack) return introTrack;
            }

            return this.cameraStream?.getAudioTracks()[0] ?? null;
        },

        syncBroadcastAudio() {
            const track = this.currentAudioTrack();
            if (!track) return;

            this.stream?.getAudioTracks().forEach((audioTrack) => this.stream.removeTrack(audioTrack));
            this.stream?.addTrack(track);

            this.peer?.getSenders().forEach((sender) => {
                if (sender.track?.kind === 'audio') sender.replaceTrack(track);
            });
        },

        setProductOverlay(product) {
            this.overlayProduct = product;
            this.overlayProductImage = null;

            if (!product?.image) return;

            const image = new Image();
            image.onload = () => { this.overlayProductImage = image; };
            image.src = product.image;
        },

        drawBroadcastOverlays(context) {
            this.drawPollOverlay(context);
            this.drawProductOverlay(context);
            this.drawReactionOverlays(context);
        },

        setPollOverlay(poll) {
            this.overlayPoll = poll;
        },

        drawPollOverlay(context) {
            if (!this.overlayPoll) return;

            const canvas = this.$refs.cameraCanvas;
            const width = Math.min(720, canvas.width - 72);
            const x = (canvas.width - width) / 2;
            const options = this.overlayPoll.options || [];
            const height = 108 + options.length * 50;
            const y = Math.max(36, canvas.height - height - (this.overlayProduct ? 190 : 54));

            context.save();
            context.fillStyle = 'rgba(255, 255, 255, 0.96)';
            context.beginPath();
            context.roundRect(x, y, width, height, 22);
            context.fill();

            context.fillStyle = '#18181b';
            context.textAlign = 'center';
            context.font = '700 24px Poppins, sans-serif';
            context.fillText(this.truncateOverlayText(this.overlayPoll.question, 54), canvas.width / 2, y + 34);
            context.fillStyle = '#71717a';
            context.font = '500 14px Poppins, sans-serif';
            context.fillText(this.overlayPoll.votesLabel || '', canvas.width / 2, y + 57);

            context.textAlign = 'left';
            options.forEach((option, index) => {
                const optionX = x + 18;
                const optionY = y + 72 + index * 50;
                const optionWidth = width - 36;
                const percentage = Math.max(0, Math.min(100, Number(option.percentage) || 0));

                context.fillStyle = '#f4f4f5';
                context.beginPath();
                context.roundRect(optionX, optionY, optionWidth, 38, 11);
                context.fill();

                if (percentage > 0) {
                    context.save();
                    context.beginPath();
                    context.roundRect(optionX, optionY, optionWidth, 38, 11);
                    context.clip();
                    context.fillStyle = '#e4e4e7';
                    context.fillRect(optionX, optionY, optionWidth * percentage / 100, 38);
                    context.restore();
                }

                context.fillStyle = '#27272a';
                context.font = '600 16px Poppins, sans-serif';
                context.fillText(this.truncateOverlayText(option.label, 42), optionX + 12, optionY + 25);
                context.textAlign = 'right';
                context.fillText(`${percentage.toFixed(1)}%`, optionX + optionWidth - 12, optionY + 25);
                context.textAlign = 'left';
            });

            context.restore();
        },

        drawProductOverlay(context) {
            if (!this.overlayProduct) return;

            const canvas = this.$refs.cameraCanvas;
            const x = 36;
            const y = canvas.height - 154;
            const width = Math.min(620, canvas.width - 72);
            const height = 112;

            context.save();
            context.fillStyle = 'rgba(255, 255, 255, 0.96)';
            context.beginPath();
            context.roundRect(x, y, width, height, 22);
            context.fill();

            const imageSize = 84;
            const imageX = x + 14;
            const imageY = y + 14;
            context.fillStyle = '#f4f4f5';
            context.beginPath();
            context.roundRect(imageX, imageY, imageSize, imageSize, 14);
            context.fill();

            if (this.overlayProductImage) {
                context.save();
                context.beginPath();
                context.roundRect(imageX, imageY, imageSize, imageSize, 14);
                context.clip();
                context.drawImage(this.overlayProductImage, imageX, imageY, imageSize, imageSize);
                context.restore();
            }

            context.fillStyle = '#18181b';
            context.font = '600 24px Poppins, sans-serif';
            context.fillText(this.truncateOverlayText(this.overlayProduct.name, 34), x + 116, y + 42);
            context.fillStyle = '#c5231b';
            context.font = '700 28px Poppins, sans-serif';
            context.fillText(this.overlayProduct.price || '', x + 116, y + 78);
            context.fillStyle = '#71717a';
            context.font = '500 15px Poppins, sans-serif';
            context.fillText(this.overlayProduct.availability || '', x + 116, y + 99);
            context.restore();
        },

        drawReactionOverlays(context) {
            const now = performance.now();
            const canvas = this.$refs.cameraCanvas;

            context.save();
            context.textAlign = 'center';
            context.font = '52px Apple Color Emoji, Segoe UI Emoji, sans-serif';

            this.floatingReactions.forEach((reaction) => {
                const progress = Math.min((now - reaction.startedAt) / 1800, 1);
                const x = canvas.width - 76 + reaction.x * 2;
                const y = canvas.height - 90 - progress * 330;
                context.globalAlpha = Math.max(0, 1 - Math.max(0, progress - 0.72) / 0.28);
                context.fillText(reaction.emoji, x, y);
            });

            context.restore();
        },

        truncateOverlayText(value, length) {
            const text = String(value || '');
            return text.length > length ? `${text.slice(0, length - 1)}…` : text;
        },

        stopEffectsPipeline() {
            this.effectsReady = false;

            if (this.effectsFrame) {
                window.cancelAnimationFrame(this.effectsFrame);
                this.effectsFrame = null;
            }

            this.canvasStream?.getTracks().forEach((track) => track.stop());
            this.canvasStream = null;
        },

        toggleCamera() {
            this.cameraEnabled = !this.cameraEnabled;
            this.cameraStream?.getVideoTracks().forEach((track) => { track.enabled = this.cameraEnabled; });
        },

        toggleMicrophone() {
            this.microphoneEnabled = !this.microphoneEnabled;
            this.cameraStream?.getAudioTracks().forEach((track) => { track.enabled = this.microphoneEnabled; });
        },

        burstReaction(emoji) {
            const id = Date.now() + Math.random();
            this.floatingReactions.push({ id, emoji, x: Math.round(Math.random() * 24) - 12, startedAt: performance.now() });
            window.setTimeout(() => {
                this.floatingReactions = this.floatingReactions.filter((reaction) => reaction.id !== id);
            }, 1800);
        },

        async startBroadcast() {
            if (!this.stream) await this.startPreview();
            if (!this.stream || this.state === 'error') return;

            this.state = 'connecting';
            this.stopping = false;
            this.message = 'Conectando con Merkamigo…';

            try {
                this.peer = new RTCPeerConnection({ sdpSemantics: 'unified-plan' });
                this.stream.getTracks().forEach((track) => this.peer.addTrack(track, this.stream));
                const offer = await this.peer.createOffer();
                await this.peer.setLocalDescription(offer);
                await this.waitForIceGathering();

                const response = await fetch(options.publishUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/sdp',
                        'X-CSRF-TOKEN': options.csrf,
                    },
                    body: this.peer.localDescription.sdp,
                });

                if (response.status !== 201) throw new Error(await response.text());

                this.session = response.headers.get('X-Whip-Session');
                await this.peer.setRemoteDescription({ type: 'answer', sdp: await response.text() });
                this.peer.onconnectionstatechange = () => {
                    if (['failed', 'disconnected'].includes(this.peer?.connectionState)) {
                        this.scheduleReconnect();
                    }
                };
                this.state = 'live';
                this.reconnectAttempts = 0;
                this.message = '';
            } catch (_error) {
                this.peer?.close();
                this.peer = null;
                this.state = 'error';
                this.message = 'No fue posible iniciar la transmisión. Verifica que el servidor de Live esté disponible.';
            }
        },

        async resumeBroadcast() {
            if (this.state === 'connecting') return;

            if (this.state === 'live' && ['connected', 'connecting', 'new'].includes(this.peer?.connectionState)) {
                return;
            }

            this.peer?.close();
            this.peer = null;
            this.session = null;

            await this.startBroadcast();
        },

        scheduleReconnect() {
            if (this.stopping || this.reconnectTimer) return;

            this.state = 'connecting';
            this.message = 'Reconectando la transmisión…';
            const delay = Math.min(2000 * (this.reconnectAttempts + 1), 10000);
            this.reconnectAttempts += 1;
            this.reconnectTimer = window.setTimeout(async () => {
                this.reconnectTimer = null;
                await this.resumeBroadcast();
            }, delay);
        },

        async stopBroadcast() {
            this.stopping = true;
            window.clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
            if (this.session) {
                const response = await fetch(options.stopUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': options.csrf,
                        'X-Whip-Session': this.session,
                    },
                }).catch(() => null);

                if (!response?.ok) {
                    this.message = 'No fue posible detener la transmisión. Intenta nuevamente.';
                    return;
                }
            }

            this.session = null;
            this.peer?.close();
            this.peer = null;
            this.state = 'ended';
            this.message = '';
            window.location.assign(options.returnUrl);
        },

        waitForIceGathering() {
            if (this.peer.iceGatheringState === 'complete') return Promise.resolve();

            return new Promise((resolve) => {
                const timeout = window.setTimeout(resolve, 4000);
                const listener = () => {
                    if (this.peer?.iceGatheringState === 'complete') {
                        window.clearTimeout(timeout);
                        this.peer.removeEventListener('icegatheringstatechange', listener);
                        resolve();
                    }
                };
                this.peer.addEventListener('icegatheringstatechange', listener);
            });
        },

        stopTracks() {
            this.stopEffectsPipeline();
            this.$refs.introSource?.pause();
            this.introStream?.getTracks().forEach((track) => track.stop());
            this.introStream = null;
            this.cameraStream?.getTracks().forEach((track) => track.stop());
            this.cameraStream = null;
            this.stream?.getTracks().forEach((track) => track.stop());
            this.stream = null;

            const source = this.$refs?.cameraSource;
            if (source) source.srcObject = null;
        },

        destroy() {
            this.stopping = true;
            window.clearTimeout(this.reconnectTimer);
            this.peer?.close();
            this.stopTracks();
        },
    };
};
