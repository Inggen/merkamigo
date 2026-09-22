// Reproductor WebRTC (WHEP) de la señal EN VIVO del Live. Los navegadores
// publican VP8/Opus (WebRTC nativo) y el muxer HLS de MediaMTX solo soporta
// H.264, así que la señal en vivo se consume por WHEP — mismo códec que
// publica el vendedor, sin transcodificación y con latencia sub-segundo.
// Mientras no hay señal WHEP se ve el reproductor HLS (poster/repetición).
window.merkamigoLiveViewer = function (options) {
    return {
        state: 'connecting', // connecting | live | offline | unsupported
        peer: null,
        session: null,
        restartTimer: null,
        restartAttempts: 0,

        init() {
            if (!window.RTCPeerConnection) {
                this.state = 'unsupported';
                return;
            }

            this.connect();
        },

        destroy() {
            window.clearTimeout(this.restartTimer);
            this.closeSession();
        },

        async connect() {
            if (!options.whepUrl) {
                this.state = 'offline';
                return;
            }

            this.closeSession();
            this.state = 'connecting';

            try {
                this.peer = new RTCPeerConnection();

                this.peer.addTransceiver('video', { direction: 'recvonly' });
                this.peer.addTransceiver('audio', { direction: 'recvonly' });

                this.peer.ontrack = (event) => {
                    this.$refs.video.srcObject = event.streams[0];
                    this.$refs.video.play().catch(() => {});
                    this.state = 'live';
                    this.restartAttempts = 0;
                };

                this.peer.onconnectionstatechange = () => {
                    if (['failed', 'disconnected', 'closed'].includes(this.peer?.connectionState)) {
                        this.scheduleReconnect();
                    }
                };

                const offer = await this.peer.createOffer();
                await this.peer.setLocalDescription(offer);
                await this.waitForIceGathering();

                const response = await fetch(options.whepUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/sdp',
                        'X-CSRF-TOKEN': options.csrf,
                    },
                    body: this.peer.localDescription.sdp,
                });

                if (!response.ok) {
                    this.state = 'offline';
                    this.scheduleReconnect();
                    return;
                }

                // Sesión opaca (encryptada en el proxy): se devuelve tal cual
                // en DELETE para liberar la lectura al salir de la página.
                this.session = response.headers.get('X-Whep-Session');
                await this.peer.setRemoteDescription({ type: 'answer', sdp: await response.text() });
            } catch (_error) {
                this.state = 'offline';
                this.scheduleReconnect();
            }
        },

        scheduleReconnect() {
            if (this.restartTimer) return;

            this.closeSession();
            this.state = 'offline';

            const delay = Math.min(3000 * 2 ** this.restartAttempts, 10000);
            this.restartAttempts += 1;
            this.restartTimer = window.setTimeout(() => {
                this.restartTimer = null;
                this.connect();
            }, delay);
        },

        closeSession() {
            if (this.session) {
                // keepalive para que el DELETE sobreviva al cierre de la página.
                fetch(options.whepUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-Whep-Session': this.session,
                        'X-CSRF-TOKEN': options.csrf,
                    },
                    keepalive: true,
                }).catch(() => {});
            }

            this.session = null;
            this.peer?.close();
            this.peer = null;
        },

        waitForIceGathering() {
            if (this.peer.iceGatheringState === 'complete') return Promise.resolve();

            return new Promise((resolve) => {
                const timeout = window.setTimeout(resolve, 3000);
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
    };
};
